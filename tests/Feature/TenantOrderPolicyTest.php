<?php

namespace Tests\Feature;

use App\Models\Canteen;
use App\Models\CommissionScheme;
use App\Models\Tenant;
use App\Models\TenantOrder;
use App\Models\User;
use App\Models\UserTenantRole;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class TenantOrderPolicyTest extends TestCase
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

    private function createTenantOrder(Tenant $tenant): TenantOrder
    {
        app(TenantContext::class)->set($tenant);

        $commission = CommissionScheme::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $orderId = DB::table('orders')->insertGetId([
            'public_id' => (string) Str::uuid(),
            'order_number' => 'ORD-'.Str::upper(Str::random(8)),
            'canteen_id' => $tenant->canteen_id,
            'checkout_key' => 'checkout-'.Str::random(12),
            'tracking_token_hash' => random_bytes(32),
            'status' => 'pending',
            'grand_total_amount' => 50000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tenantOrderId = DB::table('tenant_orders')->insertGetId([
            'order_id' => $orderId,
            'tenant_id' => $tenant->id,
            'commission_id' => $commission->id,
            'status' => 'pending',
            'commission_rate_snapshot' => 0.1500,
            'subtotal_amount' => 50000,
            'commission_amount' => 7500,
            'net_amount' => 42500,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return TenantOrder::query()->findOrFail($tenantOrderId);
    }

    public function test_user_can_view_tenant_order_from_active_tenant(): void
    {
        $canteen = Canteen::factory()->create();
        $tenant = $this->createTenant($canteen);
        $user = $this->createMember($tenant);

        $tenantOrder = $this->createTenantOrder($tenant);

        app(TenantContext::class)->set($tenant);

        $this->assertTrue(
            $user->can('view', $tenantOrder),
        );
    }

    public function test_user_cannot_view_tenant_order_from_another_tenant(): void
    {
        $canteen = Canteen::factory()->create();

        $tenantA = $this->createTenant($canteen);
        $tenantB = $this->createTenant($canteen);

        $user = $this->createMember($tenantA);

        $tenantOrderB = $this->createTenantOrder($tenantB);

        app(TenantContext::class)->set($tenantA);

        $this->assertFalse(
            $user->can('view', $tenantOrderB),
        );
    }

    public function test_suspended_user_cannot_access_tenant_order(): void
    {
        $canteen = Canteen::factory()->create();
        $tenant = $this->createTenant($canteen);
        $user = $this->createMember($tenant, 'suspended');

        $tenantOrder = $this->createTenantOrder($tenant);

        app(TenantContext::class)->set($tenant);

        $this->assertFalse(
            $user->can('view', $tenantOrder),
        );
    }

    public function test_user_without_tenant_membership_cannot_access_tenant_order(): void
    {
        $canteen = Canteen::factory()->create();
        $tenant = $this->createTenant($canteen);

        $user = User::factory()->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $tenantOrder = $this->createTenantOrder($tenant);

        app(TenantContext::class)->set($tenant);

        $this->assertFalse(
            $user->can('view', $tenantOrder),
        );
    }
}
