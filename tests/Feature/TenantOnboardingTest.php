<?php

namespace Tests\Feature;

use App\Models\Canteen;
use App\Models\Tenant;
use App\Models\TenantBankAccount;
use App\Models\User;
use App\Models\UserCanteenRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TenantOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_create_tenant_with_owner_and_primary_bank_account(): void
    {
        Notification::fake();

        $canteen = Canteen::factory()->create();

        $manager = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        UserCanteenRole::create([
            'user_id' => $manager->id,
            'canteen_id' => $canteen->id,
            'role' => 'manager',
        ]);

        $response = $this->actingAs($manager)
            ->post(route('admin.tenants.store'), [
                'code' => 'TEN-HTTP',
                'slug' => 'tenant-http',
                'display_name' => 'Tenant HTTP',
                'commission_rate' => '15',
                'pic_name' => 'Owner HTTP',
                'pic_email' => 'owner-http@example.com',
                'bank_code' => 'BCA',
                'account_holder' => 'Owner HTTP',
                'account_number' => '123456789012',
            ]);

        $tenant = Tenant::query()
            ->where('code', 'TEN-HTTP')
            ->firstOrFail();

        $response->assertRedirect(route('admin.tenants.edit', $tenant));

        $this->assertDatabaseHas('tenant_balances', [
            'tenant_id' => $tenant->id,
            'available_amount' => 0,
            'held_amount' => 0,
        ]);

        $this->assertDatabaseHas('commission_schemes', [
            'tenant_id' => $tenant->id,
            'commission_rate' => '0.1500',
            'valid_to' => null,
        ]);

        $owner = User::query()
            ->where('email', 'owner-http@example.com')
            ->firstOrFail();

        $this->assertSame('active', $owner->status);
        $this->assertSame('tenant', $owner->role);

        $this->assertDatabaseHas('user_tenant_roles', [
            'user_id' => $owner->id,
            'tenant_id' => $tenant->id,
            'role' => 'owner',
        ]);

        $account = TenantBankAccount::query()
            ->where('tenant_id', $tenant->id)
            ->firstOrFail();

        $this->assertSame('9012', $account->account_last4);
        $this->assertTrue($account->is_primary);
        $this->assertSame('unverified', $account->status);

        $storedNumber = DB::table('tenant_bank_accounts')
            ->where('id', $account->id)
            ->value('account_number_cipher');

        $this->assertNotSame('123456789012', $storedNumber);
        $this->assertSame(
            '123456789012',
            $account->fresh()->account_number_cipher,
        );

        $this->assertDatabaseHas('audit_logs', [
            'entity' => 'tenant',
            'entity_id' => $tenant->id,
            'action' => 'created',
            'tenant_id' => $tenant->id,
        ]);
    }
}
