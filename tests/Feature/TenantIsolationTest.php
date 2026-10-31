<?php

namespace Tests\Feature;

use App\Models\Canteen;
use App\Models\Menu;
use App\Models\MenuCategory;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserTenantRole;
use App\Policies\MenuPolicy;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Matriks isolasi tenant (allow/deny) A vs B:
 * global scope, scoped binding, policy, dan context lifecycle.
 */
class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: Tenant, 1: Collection<int, Menu>} */
    private function tenantWithMenus(
        int $count,
        ?Canteen $canteen = null,
        string $status = 'active',
    ): array {
        $canteen ??= Canteen::factory()->create();

        $tenant = Tenant::factory()->create([
            'canteen_id' => $canteen->id,
            'status' => $status,
        ]);

        app(TenantContext::class)->set($tenant);

        $category = MenuCategory::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $menus = Menu::factory()
            ->count($count)
            ->create([
                'tenant_id' => $tenant->id,
                'category_id' => $category->id,
            ]);

        return [$tenant, $menus];
    }

    private function memberOf(Tenant $tenant): User
    {
        $user = User::factory()->create([
            'role' => 'tenant',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        UserTenantRole::create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'role' => 'operator',
        ]);

        return $user;
    }

    public function test_global_scope_returns_only_active_tenant_rows(): void
    {
        [$tenantA] = $this->tenantWithMenus(2);
        $this->tenantWithMenus(3);

        app(TenantContext::class)->set($tenantA);

        $this->assertSame(
            2,
            Menu::query()->count(),
        );

        $this->assertTrue(
            Menu::query()
                ->get()
                ->every(
                    fn (Menu $menu) => $menu->tenant_id === $tenantA->id,
                ),
        );
    }

    public function test_without_context_scope_is_fail_closed(): void
    {
        $this->tenantWithMenus(2);
        $this->tenantWithMenus(3);

        app(TenantContext::class)->clear();

        $this->assertSame(
            0,
            Menu::query()->count(),
        );
    }

    public function test_context_is_reset_between_requests(): void
    {
        [$tenant] = $this->tenantWithMenus(1);

        app(TenantContext::class)->set($tenant);

        $this->assertTrue(
            app(TenantContext::class)->has(),
        );

        $this->app->forgetScopedInstances();

        $this->assertFalse(
            app(TenantContext::class)->has(),
            'TenantContext bocor antar request/job',
        );
    }

    public function test_tenant_id_is_autofilled_on_create_within_context(): void
    {
        [$tenant] = $this->tenantWithMenus(0);

        app(TenantContext::class)->set($tenant);

        $category = MenuCategory::create([
            'name' => 'Auto Fill',
        ]);

        $this->assertSame(
            $tenant->id,
            $category->tenant_id,
        );
    }

    public function test_scoped_binding_blocks_cross_tenant_menu(): void
    {
        [$tenantA] = $this->tenantWithMenus(1);
        [, $menusB] = $this->tenantWithMenus(1);

        $menuB = $menusB->first();

        $this->actingAs(
            $this->memberOf($tenantA),
        );

        $this->getJson(
            route('tenant.menus.show', [
                'tenant' => $tenantA->slug,
                'menu' => $menuB->id,
            ]),
        )->assertNotFound();
    }

    public function test_policy_denies_cross_tenant_update(): void
    {
        [$tenantA] = $this->tenantWithMenus(0);
        [, $menusB] = $this->tenantWithMenus(1);

        $menuB = $menusB->first();

        app(TenantContext::class)->set($tenantA);

        $this->assertFalse(
            (new MenuPolicy)->update(
                new User,
                $menuB,
            ),
            'Policy harus menolak menu tenant lain',
        );
    }
}
