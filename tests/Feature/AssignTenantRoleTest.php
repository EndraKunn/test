<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\UserTenantRole;
use App\Modules\Admin\Services\AssignTenantRole;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignTenantRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_assigns_a_role_and_records_audit(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();

        $membership = app(AssignTenantRole::class)->assign(
            $tenant,
            $user,
            'owner',
        );

        $this->assertDatabaseHas('user_tenant_roles', [
            'id' => $membership->id,
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'role' => 'owner',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'entity' => 'user_tenant_role',
            'entity_id' => $membership->id,
            'action' => 'assigned',
            'tenant_id' => $tenant->id,
        ]);
    }

    public function test_assigning_the_same_role_does_not_duplicate_membership(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();

        $service = app(AssignTenantRole::class);

        $service->assign($tenant, $user, 'operator');
        $service->assign($tenant, $user, 'operator');

        $this->assertSame(
            1,
            UserTenantRole::query()
                ->where('tenant_id', $tenant->id)
                ->where('user_id', $user->id)
                ->where('role', 'operator')
                ->count(),
        );
    }

    public function test_it_removes_a_role_and_records_audit(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();

        UserTenantRole::create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'role' => 'cashier',
        ]);

        app(AssignTenantRole::class)->remove(
            $tenant,
            $user,
            'cashier',
        );

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

    public function test_it_prevents_removing_the_last_owner(): void
    {
        $tenant = Tenant::factory()->create();
        $owner = User::factory()->create();

        UserTenantRole::create([
            'user_id' => $owner->id,
            'tenant_id' => $tenant->id,
            'role' => 'owner',
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage(
            'Tidak boleh menghapus owner terakhir tenant.',
        );

        app(AssignTenantRole::class)->remove(
            $tenant,
            $owner,
            'owner',
        );
    }
}
