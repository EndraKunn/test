<?php

namespace Tests\Feature;

use App\Models\Canteen;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserCanteenRole;
use App\Models\UserTenantRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTenantRoleTest extends TestCase
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

    private function tenantFor(Canteen $canteen): Tenant
    {
        return Tenant::factory()->create([
            'canteen_id' => $canteen->id,
            'status' => 'active',
        ]);
    }

    private function tenantUser(string $email): User
    {
        return User::factory()->create([
            'name' => 'Pengguna Tenant',
            'email' => $email,
            'role' => 'tenant',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
    }

    public function test_manager_can_assign_tenant_role(): void
    {
        $canteen = Canteen::factory()->create();
        $tenant = $this->tenantFor($canteen);
        $manager = $this->managerFor($tenant);
        $user = $this->tenantUser('operator-role@example.com');

        $response = $this->actingAs($manager)->post(
            route('admin.tenants.roles.store', $tenant),
            [
                'email' => $user->email,
                'role' => 'operator',
            ],
        );

        $response->assertRedirect(route('admin.tenants.edit', $tenant))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('user_tenant_roles', [
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'role' => 'operator',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'entity' => 'user_tenant_role',
            'action' => 'assigned',
            'tenant_id' => $tenant->id,
        ]);
    }

    public function test_manager_can_remove_tenant_role(): void
    {
        $canteen = Canteen::factory()->create();
        $tenant = $this->tenantFor($canteen);
        $manager = $this->managerFor($tenant);
        $user = $this->tenantUser('cashier-role@example.com');

        UserTenantRole::create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'role' => 'cashier',
        ]);

        $response = $this->actingAs($manager)->delete(
            route('admin.tenants.roles.destroy', [$tenant, $user]),
            ['role' => 'cashier'],
        );

        $response->assertRedirect(route('admin.tenants.edit', $tenant))
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('user_tenant_roles', [
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'role' => 'cashier',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'entity' => 'user_tenant_role',
            'action' => 'removed',
            'tenant_id' => $tenant->id,
        ]);
    }

    public function test_last_owner_cannot_be_removed_through_http(): void
    {
        $canteen = Canteen::factory()->create();
        $tenant = $this->tenantFor($canteen);
        $manager = $this->managerFor($tenant);
        $owner = $this->tenantUser('last-owner@example.com');

        UserTenantRole::create([
            'user_id' => $owner->id,
            'tenant_id' => $tenant->id,
            'role' => 'owner',
        ]);

        $response = $this->actingAs($manager)->delete(
            route('admin.tenants.roles.destroy', [$tenant, $owner]),
            ['role' => 'owner'],
        );

        $response->assertRedirect(route('admin.tenants.edit', $tenant))
            ->assertSessionHasErrors('role');

        $this->assertDatabaseHas('user_tenant_roles', [
            'user_id' => $owner->id,
            'tenant_id' => $tenant->id,
            'role' => 'owner',
        ]);
    }

    public function test_admin_without_canteen_membership_cannot_assign_role(): void
    {
        $canteen = Canteen::factory()->create();
        $tenant = $this->tenantFor($canteen);
        $outsider = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $user = $this->tenantUser('outsider-target@example.com');

        $this->actingAs($outsider)
            ->post(route('admin.tenants.roles.store', $tenant), [
                'email' => $user->email,
                'role' => 'operator',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('user_tenant_roles', [
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'role' => 'operator',
        ]);
    }
}
