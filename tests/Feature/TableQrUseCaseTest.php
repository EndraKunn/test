<?php

namespace Tests\Feature;

use App\Models\Canteen;
use App\Models\DiningTable;
use App\Models\User;
use App\Models\UserCanteenRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TableQrUseCaseTest extends TestCase
{
    use RefreshDatabase;

    private function managerFor(Canteen $canteen): User
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        UserCanteenRole::query()->create([
            'user_id' => $user->id,
            'canteen_id' => $canteen->id,
            'role' => 'manager',
        ]);

        return $user;
    }

    public function test_manager_can_view_own_canteen_tables(): void
    {
        $canteen = Canteen::factory()->create();

        $table = DiningTable::factory()->create([
            'canteen_id' => $canteen->id,
            'code' => 'MEJA-01',
            'label' => 'Meja Depan',
        ]);

        $this->actingAs($this->managerFor($canteen))
            ->get(route('admin.tables.index'))
            ->assertOk()
            ->assertSee('MEJA-01')
            ->assertSee('Meja Depan');
    }

    public function test_table_code_is_unique_per_canteen_only(): void
    {
        $canteenA = Canteen::factory()->create();
        $canteenB = Canteen::factory()->create();

        DiningTable::factory()->create([
            'canteen_id' => $canteenA->id,
            'code' => 'T1',
        ]);

        $this->actingAs($this->managerFor($canteenA))
            ->post(route('admin.tables.store'), [
                'code' => 'T1',
                'label' => 'Meja Duplikat',
            ])
            ->assertSessionHasErrors('code');

        $this->actingAs($this->managerFor($canteenB))
            ->post(route('admin.tables.store'), [
                'code' => 'T1',
                'label' => 'Meja Kantin B',
            ])
            ->assertRedirect(route('admin.tables.index'));

        $this->assertDatabaseHas('dining_tables', [
            'canteen_id' => $canteenB->id,
            'code' => 'T1',
            'label' => 'Meja Kantin B',
        ]);
    }

    public function test_user_without_canteen_membership_cannot_manage_tables(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('admin.tables.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('admin.tables.store'), [
                'code' => 'MEJA-01',
                'label' => 'Meja Tanpa Izin',
            ])
            ->assertForbidden();
    }

    public function test_manager_cannot_rotate_qr_for_another_canteen_table(): void
    {
        $canteenA = Canteen::factory()->create();
        $canteenB = Canteen::factory()->create();

        $tableB = DiningTable::factory()->create([
            'canteen_id' => $canteenB->id,
            'code' => 'MEJA-B1',
        ]);

        $this->actingAs($this->managerFor($canteenA))
            ->post(route('admin.tables.rotate', $tableB))
            ->assertForbidden();

        $this->assertDatabaseCount('table_qr_tokens', 0);
    }

    public function test_manager_can_deactivate_table_and_audit_status_change(): void
    {
        $canteen = Canteen::factory()->create();

        $table = DiningTable::factory()->create([
            'canteen_id' => $canteen->id,
            'status' => 'active',
        ]);

        $this->actingAs($this->managerFor($canteen))
            ->post(route('admin.tables.status', $table), [
                'status' => 'inactive',
            ])
            ->assertRedirect(route('admin.tables.index'));

        $this->assertDatabaseHas('dining_tables', [
            'id' => $table->id,
            'status' => 'inactive',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'entity' => 'dining_table',
            'entity_id' => $table->id,
            'action' => 'deactivated',
        ]);
    }

    public function test_manager_can_reactivate_table(): void
    {
        $canteen = Canteen::factory()->create();

        $table = DiningTable::factory()->create([
            'canteen_id' => $canteen->id,
            'status' => 'inactive',
        ]);

        $this->actingAs($this->managerFor($canteen))
            ->post(route('admin.tables.status', $table), [
                'status' => 'active',
            ])
            ->assertRedirect(route('admin.tables.index'));

        $this->assertDatabaseHas('dining_tables', [
            'id' => $table->id,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'entity' => 'dining_table',
            'entity_id' => $table->id,
            'action' => 'activated',
        ]);
    }

    public function test_qr_url_is_displayed_only_once_after_rotation(): void
    {
        $canteen = Canteen::factory()->create();

        $table = DiningTable::factory()->create([
            'canteen_id' => $canteen->id,
            'code' => 'QR-ONCE',
            'label' => 'Meja QR Sekali',
            'status' => 'active',
        ]);

        $this->actingAs($this->managerFor($canteen));

        $response = $this->followingRedirects()->post(
            route('admin.tables.rotate', $table),
        );

        $response->assertOk()
            ->assertSee('Token QR baru berhasil diterbitkan')
            ->assertSee('/q/', false)
            ->assertSee('QR-ONCE');

        // Muat ulang halaman: URL yang mengandung token rahasia tidak tampil lagi.
        $this->get(route('admin.tables.qr', $table))
            ->assertOk()
            ->assertDontSee('/q/', false)
            ->assertSee('Tidak ada token baru untuk ditampilkan');
    }
}
