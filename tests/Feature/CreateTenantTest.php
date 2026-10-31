<?php

namespace Tests\Feature;

use App\Models\Canteen;
use App\Models\CommissionScheme;
use App\Models\Tenant;
use App\Modules\Admin\Services\CreateTenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateTenantTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_tenant_with_balance_commission_scheme_and_audit_log(): void
    {
        $canteen = Canteen::factory()->create();

        $tenant = app(CreateTenant::class)->handle(
            $canteen,
            [
                'code' => 'TEN-NEW',
                'slug' => 'tenant-new',
                'display_name' => 'Tenant Baru',
                'commission_rate' => '0.1500',
            ],
        );

        $this->assertInstanceOf(Tenant::class, $tenant);
        $this->assertSame($canteen->id, $tenant->canteen_id);
        $this->assertSame('TEN-NEW', $tenant->code);
        $this->assertSame('tenant-new', $tenant->slug);
        $this->assertSame('Tenant Baru', $tenant->display_name);
        $this->assertSame('active', $tenant->status);

        $this->assertDatabaseHas('tenant_balances', [
            'tenant_id' => $tenant->id,
            'available_amount' => 0,
            'held_amount' => 0,
        ]);

        $this->assertDatabaseHas('commission_schemes', [
            'tenant_id' => $tenant->id,
            'commission_rate' => '0.1500',
            'valid_to' => null,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'entity' => 'tenant',
            'entity_id' => $tenant->id,
            'action' => 'created',
            'tenant_id' => $tenant->id,
            'canteen_id' => $canteen->id,
        ]);
    }

    public function test_it_creates_only_one_active_commission_scheme_for_a_new_tenant(): void
    {
        $canteen = Canteen::factory()->create();

        $tenant = app(CreateTenant::class)->handle(
            $canteen,
            [
                'code' => 'TEN-ONE',
                'slug' => 'tenant-one',
                'display_name' => 'Tenant One',
                'commission_rate' => '0.1250',
            ],
        );

        $this->assertSame(
            1,
            CommissionScheme::query()
                ->where('tenant_id', $tenant->id)
                ->whereNull('valid_to')
                ->count(),
        );
    }

    public function test_it_rejects_duplicate_tenant_code_in_the_same_canteen(): void
    {
        $canteen = Canteen::factory()->create();

        app(CreateTenant::class)->handle(
            $canteen,
            [
                'code' => 'TEN-DUP',
                'slug' => 'tenant-one',
                'display_name' => 'Tenant One',
                'commission_rate' => '0.1500',
            ],
        );

        $this->expectException(\Throwable::class);

        app(CreateTenant::class)->handle(
            $canteen,
            [
                'code' => 'TEN-DUP',
                'slug' => 'tenant-two',
                'display_name' => 'Tenant Two',
                'commission_rate' => '0.1500',
            ],
        );
    }
}
