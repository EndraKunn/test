<?php

namespace Tests\Feature;

use App\Models\Canteen;
use App\Models\Tenant;
use App\Models\TenantBankAccount;
use App\Models\User;
use App\Models\UserCanteenRole;
use App\Models\UserTenantRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTenantPageTest extends TestCase
{
    use RefreshDatabase;

    private function managerFor(Canteen $canteen): User
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        UserCanteenRole::create([
            'user_id' => $user->id,
            'canteen_id' => $canteen->id,
            'role' => 'manager',
        ]);

        return $user;
    }

    public function test_canteen_manager_can_view_tenant_index(): void
    {
        $canteen = Canteen::factory()->create();

        $this->actingAs($this->managerFor($canteen))
            ->get(route('admin.tenants.index'))
            ->assertOk()
            ->assertSee($canteen->name);
    }

    public function test_canteen_manager_can_view_tenant_creation_form(): void
    {
        $canteen = Canteen::factory()->create();

        $this->actingAs($this->managerFor($canteen))
            ->get(route('admin.tenants.create'))
            ->assertOk()
            ->assertSee('Buat Tenant')
            ->assertSee('commission_rate');
    }

    public function test_admin_without_canteen_membership_is_forbidden(): void
    {
        $outsider = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($outsider)
            ->get(route('admin.tenants.index'))
            ->assertForbidden();
    }

    public function test_tenant_index_only_shows_tenants_for_managed_canteen(): void
    {
        $canteenA = Canteen::factory()->create();
        $canteenB = Canteen::factory()->create();

        Tenant::factory()->create([
            'canteen_id' => $canteenA->id,
            'code' => 'TEN-A',
            'slug' => 'tenant-a-test',
            'display_name' => 'Tenant Milik A',
        ]);

        Tenant::factory()->create([
            'canteen_id' => $canteenB->id,
            'code' => 'TEN-B',
            'slug' => 'tenant-b-test',
            'display_name' => 'Tenant Milik B',
        ]);

        $this->actingAs($this->managerFor($canteenA))
            ->get(route('admin.tenants.index'))
            ->assertOk()
            ->assertSee('Tenant Milik A')
            ->assertDontSee('Tenant Milik B');
    }

    public function test_index_shows_correct_status_action_for_each_tenant(): void
    {
        $canteen = Canteen::factory()->create();

        $activeTenant = Tenant::factory()->create([
            'canteen_id' => $canteen->id,
            'code' => 'TEN-ACTIVE',
            'slug' => 'tenant-active-test',
            'display_name' => 'Tenant Aktif',
            'status' => 'active',
        ]);

        $inactiveTenant = Tenant::factory()->create([
            'canteen_id' => $canteen->id,
            'code' => 'TEN-INACTIVE',
            'slug' => 'tenant-inactive-test',
            'display_name' => 'Tenant Nonaktif',
            'status' => 'inactive',
        ]);

        $response = $this->actingAs($this->managerFor($canteen))
            ->get(route('admin.tenants.index'));

        $response->assertOk()
            ->assertSee('Nonaktifkan')
            ->assertSee('Aktifkan')
            ->assertSee(route('admin.tenants.status', $activeTenant))
            ->assertSee(route('admin.tenants.status', $inactiveTenant));
    }

    public function test_edit_page_shows_commission_schedule_form(): void
    {
        $canteen = Canteen::factory()->create();

        $tenant = Tenant::factory()->create([
            'canteen_id' => $canteen->id,
            'code' => 'TEN-COMMISSION',
            'slug' => 'tenant-commission-test',
            'display_name' => 'Tenant Komisi',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->managerFor($canteen))
            ->get(route('admin.tenants.edit', $tenant));

        $response->assertOk()
            ->assertSee('Jadwalkan Komisi Baru')
            ->assertSee('Tarif Komisi (%)')
            ->assertSee('Waktu Mulai Berlaku')
            ->assertSee(route('admin.tenants.commission.store', $tenant));
    }

    public function test_edit_page_shows_bank_account_section_and_add_form(): void
    {
        $canteen = Canteen::factory()->create();

        $tenant = Tenant::factory()->create([
            'canteen_id' => $canteen->id,
            'code' => 'TEN-BANK',
            'slug' => 'tenant-bank-test',
            'display_name' => 'Tenant Bank',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->managerFor($canteen))
            ->get(route('admin.tenants.edit', $tenant));

        $response->assertOk()
            ->assertSee('Rekening Bank Tenant')
            ->assertSee('Tambah Rekening Bank')
            ->assertSee('Kode Bank')
            ->assertSee('Nama Pemilik Rekening')
            ->assertSee('Nomor Rekening')
            ->assertSee('Belum ada rekening bank yang terdaftar.')
            ->assertSee(route('admin.tenants.bank.store', $tenant));
    }

    public function test_edit_page_shows_masked_account_number_and_bank_actions(): void
    {
        $canteen = Canteen::factory()->create();

        $tenant = Tenant::factory()->create([
            'canteen_id' => $canteen->id,
            'code' => 'TEN-BANK-VIEW',
            'slug' => 'tenant-bank-view',
            'display_name' => 'Tenant Bank View',
            'status' => 'active',
        ]);

        $accountNumber = '123456789012';

        $account = new TenantBankAccount;

        $account->forceFill([
            'tenant_id' => $tenant->id,
            'account_number_cipher' => $accountNumber,
            'account_last4' => substr($accountNumber, -4),
            'bank_code' => 'BCA',
            'account_holder' => 'Pemilik Bank Test',
            'status' => 'unverified',
            'is_primary' => false,
        ]);

        $account->save();

        $response = $this->actingAs($this->managerFor($canteen))
            ->get(route('admin.tenants.edit', $tenant));

        $response->assertOk()
            ->assertSee('BCA')
            ->assertSee('Pemilik Bank Test')
            ->assertSee('Rekening berakhiran ****9012')
            ->assertSee('Verifikasi')
            ->assertSee('Tolak')
            ->assertSee('Jadikan Rekening Utama')
            ->assertDontSee($accountNumber)
            ->assertSee(route(
                'admin.tenants.bank.verify',
                [$tenant, $account],
            ))
            ->assertSee(route(
                'admin.tenants.bank.primary',
                [$tenant, $account],
            ));
    }

    public function test_edit_page_shows_tenant_members_and_role_actions(): void
    {
        $canteen = Canteen::factory()->create();

        $tenant = Tenant::factory()->create([
            'canteen_id' => $canteen->id,
            'code' => 'TEN-MEMBER',
            'slug' => 'tenant-member-test',
            'display_name' => 'Tenant Anggota',
            'status' => 'active',
        ]);

        $member = User::factory()->create([
            'name' => 'Operator Uji',
            'email' => 'operator-ui@example.com',
            'role' => 'tenant',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        UserTenantRole::create([
            'user_id' => $member->id,
            'tenant_id' => $tenant->id,
            'role' => 'operator',
        ]);

        $response = $this->actingAs($this->managerFor($canteen))
            ->get(route('admin.tenants.edit', $tenant));

        $response->assertOk()
            ->assertSee('Anggota Tenant')
            ->assertSee('Operator Uji')
            ->assertSee('operator-ui@example.com')
            ->assertSee('Tetapkan Role Baru')
            ->assertSee('Cabut Role')
            ->assertSee(route('admin.tenants.roles.store', $tenant))
            ->assertSee(route(
                'admin.tenants.roles.destroy',
                [$tenant, $member],
            ));
    }
}
