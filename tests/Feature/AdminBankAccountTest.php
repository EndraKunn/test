<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantBankAccount;
use App\Models\User;
use App\Models\UserCanteenRole;
use App\Modules\Admin\Services\ManageBankAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminBankAccountTest extends TestCase
{
    use RefreshDatabase;

    private function managerFor(Tenant $tenant): User
    {
        $manager = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        UserCanteenRole::create([
            'user_id' => $manager->id,
            'canteen_id' => $tenant->canteen_id,
            'role' => 'manager',
        ]);

        return $manager;
    }

    private function bankData(string $number = '123456789012'): array
    {
        return [
            'bank_code' => 'BCA',
            'account_holder' => 'Pemilik Rekening',
            'account_number' => $number,
        ];
    }

    public function test_manager_can_add_encrypted_bank_account(): void
    {
        $tenant = Tenant::factory()->create();
        $manager = $this->managerFor($tenant);
        $number = '123456789012';

        $response = $this->actingAs($manager)->post(
            route('admin.tenants.bank.store', $tenant),
            $this->bankData($number),
        );

        $response->assertRedirect(route('admin.tenants.edit', $tenant))
            ->assertSessionHas('status');

        $account = TenantBankAccount::query()
            ->where('tenant_id', $tenant->id)
            ->firstOrFail();

        $storedNumber = DB::table('tenant_bank_accounts')
            ->where('id', $account->id)
            ->value('account_number_cipher');

        $this->assertNotSame($number, $storedNumber);
        $this->assertSame($number, $account->account_number_cipher);
        $this->assertSame('9012', $account->account_last4);
        $this->assertFalse($account->is_primary);
    }

    public function test_manager_can_verify_bank_account(): void
    {
        $tenant = Tenant::factory()->create();
        $manager = $this->managerFor($tenant);

        $account = app(ManageBankAccount::class)->store(
            $tenant,
            $this->bankData(),
        );

        $response = $this->actingAs($manager)->post(
            route('admin.tenants.bank.verify', [$tenant, $account]),
            ['status' => 'verified'],
        );

        $response->assertRedirect(route('admin.tenants.edit', $tenant));

        $this->assertDatabaseHas('tenant_bank_accounts', [
            'id' => $account->id,
            'tenant_id' => $tenant->id,
            'status' => 'verified',
            'verified_by' => $manager->id,
        ]);
    }

    public function test_manager_can_set_primary_bank_account(): void
    {
        $tenant = Tenant::factory()->create();
        $manager = $this->managerFor($tenant);

        $account = app(ManageBankAccount::class)->store(
            $tenant,
            $this->bankData(),
        );

        $response = $this->actingAs($manager)->post(
            route('admin.tenants.bank.primary', [$tenant, $account]),
        );

        $response->assertRedirect(route('admin.tenants.edit', $tenant));

        $this->assertDatabaseHas('tenant_bank_accounts', [
            'id' => $account->id,
            'tenant_id' => $tenant->id,
            'is_primary' => true,
        ]);
    }

    public function test_manager_cannot_verify_another_tenants_account(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $managerA = $this->managerFor($tenantA);

        $accountB = app(ManageBankAccount::class)->store(
            $tenantB,
            $this->bankData(),
        );

        $this->actingAs($managerA)
            ->post(
                route('admin.tenants.bank.verify', [$tenantA, $accountB]),
                ['status' => 'verified'],
            )
            ->assertNotFound();

        $this->assertDatabaseHas('tenant_bank_accounts', [
            'id' => $accountB->id,
            'tenant_id' => $tenantB->id,
            'status' => 'unverified',
        ]);
    }

    public function test_admin_without_canteen_membership_cannot_add_bank_account(): void
    {
        $tenant = Tenant::factory()->create();

        $outsider = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($outsider)
            ->post(
                route('admin.tenants.bank.store', $tenant),
                $this->bankData(),
            )
            ->assertForbidden();

        $this->assertDatabaseMissing('tenant_bank_accounts', [
            'tenant_id' => $tenant->id,
        ]);
    }
}
