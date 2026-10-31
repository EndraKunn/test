<?php

namespace Tests\Feature;

use App\Models\CommissionScheme;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserCanteenRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCommissionTest extends TestCase
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

    public function test_manager_can_schedule_new_commission_rate(): void
    {
        $tenant = Tenant::factory()->create();

        $old = CommissionScheme::create([
            'tenant_id' => $tenant->id,
            'commission_rate' => '0.1000',
            'valid_from' => now()->subDay(),
            'valid_to' => null,
        ]);

        $manager = $this->managerFor($tenant);
        $effectiveAt = now()->addDays(2)->startOfMinute();

        $response = $this->actingAs($manager)->post(
            route('admin.tenants.commission.store', $tenant),
            [
                'commission_rate' => '15',
                'effective_at' => $effectiveAt->format('Y-m-d\TH:i'),
            ],
        );

        $response->assertRedirect(route('admin.tenants.edit', $tenant))
            ->assertSessionHas('status');

        $this->assertEquals(
            $effectiveAt->timestamp,
            $old->fresh()->valid_to->timestamp,
        );

        $new = CommissionScheme::query()
            ->where('tenant_id', $tenant->id)
            ->whereNull('valid_to')
            ->firstOrFail();

        $this->assertSame('0.1500', $new->commission_rate);
        $this->assertEquals(
            $effectiveAt->timestamp,
            $new->valid_from->timestamp,
        );

        $this->assertDatabaseHas('audit_logs', [
            'entity' => 'commission_scheme',
            'entity_id' => (string) $new->id,
            'action' => 'scheduled',
            'tenant_id' => $tenant->id,
        ]);
    }

    public function test_past_effective_time_is_rejected(): void
    {
        $tenant = Tenant::factory()->create();
        $manager = $this->managerFor($tenant);

        $this->actingAs($manager)
            ->post(route('admin.tenants.commission.store', $tenant), [
                'commission_rate' => '15',
                'effective_at' => now()->subDay()->format('Y-m-d\TH:i'),
            ])
            ->assertSessionHasErrors('effective_at');

        $this->assertDatabaseMissing('commission_schemes', [
            'tenant_id' => $tenant->id,
        ]);
    }

    public function test_admin_without_canteen_membership_cannot_schedule_commission(): void
    {
        $tenant = Tenant::factory()->create();

        $outsider = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($outsider)
            ->post(route('admin.tenants.commission.store', $tenant), [
                'commission_rate' => '15',
                'effective_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('commission_schemes', [
            'tenant_id' => $tenant->id,
        ]);
    }
}
