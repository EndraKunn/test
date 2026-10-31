<?php

namespace Tests\Feature;

use App\Models\Canteen;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserTenantRole;
use App\Models\Withdrawal;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WithdrawalPolicyTest extends TestCase
{
    use RefreshDatabase;

    private function createTenant(Canteen $canteen): Tenant
    {
        return Tenant::factory()->create([
            'canteen_id' => $canteen->id,
            'status' => 'active',
        ]);
    }

    private function createMember(
        Tenant $tenant,
        string $status = 'active',
    ): User {
        $user = User::factory()->create([
            'status' => $status,
            'email_verified_at' => now(),
        ]);

        UserTenantRole::create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'role' => 'owner',
        ]);

        return $user;
    }

    private function createWithdrawal(
        Tenant $tenant,
        User $requestedBy,
    ): Withdrawal {
        $bankAccountId = DB::table('tenant_bank_accounts')->insertGetId([
            'tenant_id' => $tenant->id,
            'verified_by' => null,
            'account_number_cipher' => 'encrypted-test-account',
            'account_last4' => '1234',
            'bank_code' => 'BCA',
            'account_holder' => 'Test Owner',
            'status' => 'verified',
            'is_primary' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $withdrawalId = DB::table('withdrawals')->insertGetId([
            'tenant_id' => $tenant->id,
            'bank_account_id' => $bankAccountId,
            'requested_by' => $requestedBy->id,
            'reviewed_by' => null,
            'idempotency_key' => 'wd-'.uniqid(),
            'amount' => 100000,
            'status' => 'requested',
            'transfer_snapshot' => json_encode([
                'bank_code' => 'BCA',
                'account_last4' => '1234',
                'account_holder' => 'Test Owner',
            ]),
            'active_tenant_lock' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Withdrawal::query()->findOrFail($withdrawalId);
    }

    public function test_user_can_view_withdrawal_from_active_tenant(): void
    {
        $canteen = Canteen::factory()->create();
        $tenant = $this->createTenant($canteen);
        $user = $this->createMember($tenant);

        app(TenantContext::class)->set($tenant);

        $withdrawal = $this->createWithdrawal($tenant, $user);

        $this->assertTrue(
            $user->can('view', $withdrawal),
        );
    }

    public function test_user_cannot_view_withdrawal_from_another_tenant(): void
    {
        $canteen = Canteen::factory()->create();

        $tenantA = $this->createTenant($canteen);
        $tenantB = $this->createTenant($canteen);

        $user = $this->createMember($tenantA);

        app(TenantContext::class)->set($tenantB);

        $withdrawalB = $this->createWithdrawal($tenantB, $user);

        app(TenantContext::class)->set($tenantA);

        $this->assertFalse(
            $user->can('view', $withdrawalB),
        );
    }

    public function test_suspended_user_cannot_access_withdrawal(): void
    {
        $canteen = Canteen::factory()->create();
        $tenant = $this->createTenant($canteen);
        $user = $this->createMember($tenant, 'suspended');

        app(TenantContext::class)->set($tenant);

        $withdrawal = $this->createWithdrawal($tenant, $user);

        $this->assertFalse(
            $user->can('view', $withdrawal),
        );
    }

    public function test_user_without_tenant_membership_cannot_access_withdrawal(): void
    {
        $canteen = Canteen::factory()->create();
        $tenant = $this->createTenant($canteen);

        $user = User::factory()->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        app(TenantContext::class)->set($tenant);

        $withdrawal = $this->createWithdrawal($tenant, $user);

        $this->assertFalse(
            $user->can('view', $withdrawal),
        );
    }
}
