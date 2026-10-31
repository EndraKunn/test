<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\UserCanteenRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTenantStatusTest extends TestCase
{
    use RefreshDatabase;

    private function managerFor(Tenant $tenant): User
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        UserCanteenRole::create([
            'user_id' => $user->id,
            'canteen_id' => $tenant->canteen_id,
            'role' => 'manager',
        ]);

        return $user;
    }

    public function test_manager_can_deactivate_tenant_and_audit_is_recorded(): void
    {
        $tenant = Tenant::factory()->create([
            'status' => 'active',
        ]);

        $manager = $this->managerFor($tenant);

        $response = $this->actingAs($manager)->post(
            route('admin.tenants.status', $tenant),
            ['status' => 'inactive'],
        );

        $response->assertRedirect(route('admin.tenants.index'));

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'status' => 'inactive',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'entity' => 'tenant',
            'entity_id' => (string) $tenant->id,
            'action' => 'deactivated',
            'tenant_id' => $tenant->id,
        ]);
    }

    public function test_manager_can_activate_inactive_tenant(): void
    {
        $tenant = Tenant::factory()->create([
            'status' => 'inactive',
        ]);

        $manager = $this->managerFor($tenant);

        $response = $this->actingAs($manager)->post(
            route('admin.tenants.status', $tenant),
            ['status' => 'active'],
        );

        $response->assertRedirect(route('admin.tenants.index'));

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'entity' => 'tenant',
            'entity_id' => (string) $tenant->id,
            'action' => 'activated',
            'tenant_id' => $tenant->id,
        ]);
    }

    public function test_admin_without_canteen_membership_cannot_change_tenant_status(): void
    {
        $tenant = Tenant::factory()->create([
            'status' => 'active',
        ]);

        $outsider = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($outsider)
            ->post(route('admin.tenants.status', $tenant), [
                'status' => 'inactive',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'status' => 'active',
        ]);
    }

    public function test_invalid_status_is_rejected_by_http_validation(): void
    {
        $tenant = Tenant::factory()->create([
            'status' => 'active',
        ]);

        $manager = $this->managerFor($tenant);

        $this->actingAs($manager)
            ->post(route('admin.tenants.status', $tenant), [
                'status' => 'suspended',
            ])
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'status' => 'active',
        ]);
    }
}
