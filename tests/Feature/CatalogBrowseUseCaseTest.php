<?php

namespace Tests\Feature;

use App\Models\Canteen;
use App\Models\CommissionScheme;
use App\Models\Menu;
use App\Models\MenuCategory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Tenant;
use App\Models\TenantOperatingHour;
use App\Models\TenantOrder;
use App\Modules\Kitchen\Services\WaitTimeEstimator;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class CatalogBrowseUseCaseTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Membuat menu dan kategori untuk tenant tertentu.
     */
    private function menu(
        Tenant $tenant,
        string $category,
        array $attrs = [],
    ): Menu {
        // TenantContext wajib aktif sebelum membuat data tenant.
        app(TenantContext::class)->set($tenant);

        $cat = MenuCategory::query()
            ->withoutGlobalScope('tenant')
            ->where([
                'tenant_id' => $tenant->id,
                'name' => $category,
            ])
            ->first()
            ?? MenuCategory::factory()->create([
                'tenant_id' => $tenant->id,
                'name' => $category,
            ]);

        return Menu::factory()->create(array_merge([
            'tenant_id' => $tenant->id,
            'category_id' => $cat->id,
            'is_available' => true,
            'stock_qty' => 10,
            'prep_minutes' => 8,
        ], $attrs));
    }

    /**
     * Katalog mengelompokkan menu berdasarkan tenant,
     * serta mendukung pencarian dan filter kategori.
     */
    public function test_catalog_groups_by_tenant_and_filters_by_tenant_name_and_category(): void
    {
        $canteen = Canteen::factory()->create();

        $rina = Tenant::factory()->create([
            'canteen_id' => $canteen->id,
            'display_name' => 'Warung Bu Rina',
            'status' => 'active',
        ]);

        $kopi = Tenant::factory()->create([
            'canteen_id' => $canteen->id,
            'display_name' => 'Kopi Serambi',
            'status' => 'active',
        ]);

        $this->menu($rina, 'Makanan utama', [
            'name' => 'Nasi Ayam Bakar',
            'description' => 'Ayam bakar kecap',
        ]);

        $this->menu($kopi, 'Minuman', [
            'name' => 'Es Kopi Susu',
        ]);

        Livewire::test('catalog::menu-catalog', [
            'canteenSlug' => $canteen->slug,
        ])
            ->assertSeeInOrder([
                'Warung Bu Rina',
                'Nasi Ayam Bakar',
                'Ayam bakar kecap',
                'Kopi Serambi',
                'Es Kopi Susu',
            ])
            ->set('search', 'Serambi')
            ->assertSee('Es Kopi Susu')
            ->assertDontSee('Nasi Ayam Bakar')
            ->set('search', '')
            ->call('filterCategory', 'Makanan utama')
            ->assertSee('Nasi Ayam Bakar')
            ->assertDontSee('Es Kopi Susu');
    }

    /**
     * Tenant tutup menampilkan jam buka dan menu tidak dapat dijual.
     */
    public function test_closed_tenant_shows_opening_time_and_items_are_not_sellable(): void
    {
        config(['app.timezone' => 'Asia/Jakarta']);

        $canteen = Canteen::factory()->create();

        $geprek = Tenant::factory()->create([
            'canteen_id' => $canteen->id,
            'display_name' => 'Geprek Juara',
            'status' => 'active',
        ]);

        // Atur konteks sebelum membuat jam operasional.
        app(TenantContext::class)->set($geprek);

        foreach (range(0, 6) as $day) {
            (new TenantOperatingHour)->forceFill([
                'tenant_id' => $geprek->id,
                'day_of_week' => $day,
                'opens_at' => '10:00:00',
                'closes_at' => '20:00:00',
            ])->save();
        }

        $this->menu($geprek, 'Makanan utama', [
            'name' => 'Ayam Geprek',
        ]);

        // Simulasikan hari Kamis pukul 08.00 WIB.
        $this->travelTo(
            Carbon::parse('2026-09-24 08:00:00', 'Asia/Jakarta')
        );

        Livewire::test('catalog::menu-catalog', [
            'canteenSlug' => $canteen->slug,
        ])
            ->assertSee('Tutup')
            ->assertSee('Buka pukul')
            ->assertSee('10.00')
            ->assertSeeInOrder([
                'data-sellable="0"',
                'Ayam Geprek',
            ], false);
    }

    /**
     * Estimasi antrean menggunakan status pesanan aktif
     * dan membagi waktu dengan kapasitas dapur.
     */
    public function test_wait_estimate_uses_active_queue_divided_by_capacity(): void
    {
        $canteen = Canteen::factory()->create();

        $tenant = Tenant::factory()->create([
            'canteen_id' => $canteen->id,
            'status' => 'active',
        ]);

        $this->menu($tenant, 'Makanan utama', [
            'prep_minutes' => 8,
        ]);

        app(TenantContext::class)->set($tenant);

        $estimator = app(WaitTimeEstimator::class);

        // Tanpa antrean: 8 menit -> estimasi 8–12 menit.
        $this->assertSame(
            ['low' => 8, 'high' => 12],
            $estimator->forTenant($tenant)
        );

        $commission = CommissionScheme::factory()->create([
            'tenant_id' => $tenant->id,
            'commission_rate' => 0.1,
            'valid_from' => now()->subDay(),
            'valid_to' => null,
        ]);

        $menuId = Menu::query()
            ->withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->value('id');

        // Dua pesanan aktif dan satu pesanan ready.
        foreach (['accepted', 'preparing', 'ready'] as $i => $status) {
            $order = (new Order)->forceFill([
                'public_id' => (string) Str::uuid(),
                'order_number' => 'ORD-'.$i,
                'canteen_id' => $canteen->id,
                'checkout_key' => (string) Str::uuid(),
                'tracking_token_hash' => random_bytes(32),
                'status' => 'paid',
            ]);

            $order->save();

            $tenantOrder = (new TenantOrder)->forceFill([
                'order_id' => $order->id,
                'tenant_id' => $tenant->id,
                'commission_id' => $commission->id,
                'status' => $status,
                'commission_rate_snapshot' => 0.1,
                'subtotal_amount' => 10000,
                'commission_amount' => 1000,
                'net_amount' => 9000,
            ]);

            $tenantOrder->save();

            (new OrderItem)->forceFill([
                'tenant_id' => $tenant->id,
                'tenant_order_id' => $tenantOrder->id,
                'menu_id' => $menuId,
                'name_snapshot' => 'Menu Uji',
                'unit_price_snapshot' => 10000,
                'prep_minutes_snapshot' => 10,
                'quantity' => 1,
                'line_total' => 10000,
            ])->save();
        }

        // ready tidak ikut dihitung.
        // (10 + 10) / kapasitas 2 = 10 menit antrean.
        $this->assertSame(
            10.0,
            $estimator->queueMinutes($tenant),
            'Pesanan berstatus ready tidak boleh dihitung.'
        );

        // 10 menit antrean + 8 menit persiapan = 18 menit.
        $this->assertSame(
            ['low' => 18, 'high' => 27],
            $estimator->forTenant($tenant)
        );

        $this->assertSame(
            '± 18–27 mnt',
            $estimator->label($tenant)
        );
    }
}
