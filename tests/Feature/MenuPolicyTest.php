<?php

namespace Tests\Feature;

use App\Models\Canteen;
use App\Models\Menu;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserTenantRole;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_menu_from_active_tenant(): void
    {
        $canteen = Canteen::factory()->create();

        $tenant = Tenant::factory()->create([
            'canteen_id' => $canteen->id,
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        UserTenantRole::create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'role' => 'owner',
        ]);

        app(TenantContext::class)->set($tenant);

        $menu = Menu::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $this->assertTrue(
            $user->can('view', $menu),
        );
    }

    public function test_user_cannot_view_menu_from_another_tenant(): void
    {
        $canteen = Canteen::factory()->create();

        $tenantA = Tenant::factory()->create([
            'canteen_id' => $canteen->id,
            'status' => 'active',
        ]);

        $tenantB = Tenant::factory()->create([
            'canteen_id' => $canteen->id,
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        UserTenantRole::create([
            'user_id' => $user->id,
            'tenant_id' => $tenantA->id,
            'role' => 'owner',
        ]);

        app(TenantContext::class)->set($tenantB);

        $menuB = Menu::factory()->create([
            'tenant_id' => $tenantB->id,
        ]);

        app(TenantContext::class)->set($tenantA);

        $this->assertFalse(
            $user->can('view', $menuB),
        );
    }

    public function test_suspended_user_cannot_access_menu(): void
    {
        $canteen = Canteen::factory()->create();

        $tenant = Tenant::factory()->create([
            'canteen_id' => $canteen->id,
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'status' => 'suspended',
            'email_verified_at' => now(),
        ]);

        UserTenantRole::create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'role' => 'owner',
        ]);

        app(TenantContext::class)->set($tenant);

        $menu = Menu::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $this->assertFalse(
            $user->can('view', $menu),
        );
    }

    public function test_user_without_tenant_membership_cannot_access_menu(): void
    {
        $canteen = Canteen::factory()->create();

        $tenant = Tenant::factory()->create([
            'canteen_id' => $canteen->id,
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        app(TenantContext::class)->set($tenant);

        $menu = Menu::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $this->assertFalse(
            $user->can('view', $menu),
        );
    }
}
