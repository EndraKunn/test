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

class ScopedBindingTest extends TestCase
{
    use RefreshDatabase;

    private function createTenant(
        Canteen $canteen,
        string $slug,
    ): Tenant {
        return Tenant::factory()->create([
            'canteen_id' => $canteen->id,
            'slug' => $slug,
            'status' => 'active',
        ]);
    }

    private function createMember(Tenant $tenant): User
    {
        $user = User::factory()->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        UserTenantRole::create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'role' => 'owner',
        ]);

        return $user;
    }

    private function createMenu(Tenant $tenant, string $name): Menu
    {
        app(TenantContext::class)->set($tenant);

        return Menu::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => $name,
        ]);
    }

    public function test_tenant_can_access_its_own_menu(): void
    {
        $canteen = Canteen::factory()->create();

        $tenant = $this->createTenant($canteen, 'tenant-a');
        $user = $this->createMember($tenant);
        $menu = $this->createMenu($tenant, 'Menu Tenant A');

        $this->actingAs($user);

        $this->get(
            route('tenant.menus.show', [
                'tenant' => $tenant->slug,
                'menu' => $menu->id,
            ]),
        )->assertOk()
            ->assertSee('Menu Tenant A');
    }

    public function test_tenant_cannot_access_menu_from_another_tenant(): void
    {
        $canteen = Canteen::factory()->create();

        $tenantA = $this->createTenant($canteen, 'tenant-a');
        $tenantB = $this->createTenant($canteen, 'tenant-b');

        $user = $this->createMember($tenantA);
        $menuB = $this->createMenu($tenantB, 'Menu Tenant B');

        $this->actingAs($user);

        $this->get(
            route('tenant.menus.show', [
                'tenant' => $tenantA->slug,
                'menu' => $menuB->id,
            ]),
        )->assertNotFound();
    }

    public function test_user_without_tenant_membership_cannot_access_menu(): void
    {
        $canteen = Canteen::factory()->create();

        $tenant = $this->createTenant($canteen, 'tenant-a');
        $menu = $this->createMenu($tenant, 'Menu Tenant A');

        $user = User::factory()->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user);

        $this->get(
            route('tenant.menus.show', [
                'tenant' => $tenant->slug,
                'menu' => $menu->id,
            ]),
        )->assertForbidden();
    }
}
