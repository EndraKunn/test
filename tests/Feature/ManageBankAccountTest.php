<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantBankAccount;
use App\Models\User;
use App\Modules\Admin\Services\ManageBankAccount;
use DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ManageBankAccountTest extends TestCase
{
    use RefreshDatabase;

    private function bankData(string $number = '123456789012'): array
    {
        return [
            'bank_code' => 'BCA',
            'account_holder' => 'Pemilik Rekening',
            'account_number' => $number,
        ];
    }

    public function test_it_stores_encrypted_account_number_and_safe_audit_data(): void
    {
        $tenant = Tenant::factory()->create();
        $number = '123456789012';

        $account = app(ManageBankAccount::class)->store(
            $tenant,
            $this->bankData($number),
        );

        $storedValue = DB::table('tenant_bank_accounts')
            ->where('id', $account->id)
            ->value('account_number_cipher');

        $this->assertNotSame($number, $storedValue);
        $this->assertSame($number, $account->fresh()->account_number_cipher);
        $this->assertSame('9012', $account->account_last4);

        $audit = DB::table('audit_logs')
            ->where('entity', 'tenant_bank_account')
            ->where('entity_id', (string) $account->id)
            ->first();

        $this->assertNotNull($audit);
        $this->assertStringNotContainsString($number, (string) $audit->after);
        $this->assertStringNotContainsString($number, (string) $audit->before);
    }

    public function test_it_verifies_bank_account_and_records_reviewer(): void
    {
        $tenant = Tenant::factory()->create();
        $reviewer = User::factory()->create();

        $account = app(ManageBankAccount::class)->store(
            $tenant,
            $this->bankData(),
        );

        $this->actingAs($reviewer);

        $verified = app(ManageBankAccount::class)->verify(
            $tenant,
            $account,
            'verified',
        );

        $this->assertSame('verified', $verified->status);
        $this->assertSame($reviewer->id, $verified->verified_by);
    }

    public function test_only_one_bank_account_can_be_primary_per_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        $service = app(ManageBankAccount::class);

        $first = $service->store(
            $tenant,
            $this->bankData('111111111111'),
        );

        $second = $service->store(
            $tenant,
            $this->bankData('222222222222'),
        );

        $service->makePrimary($tenant, $first);
        $service->makePrimary($tenant, $second);

        $this->assertFalse($first->fresh()->is_primary);
        $this->assertTrue($second->fresh()->is_primary);

        $this->assertSame(
            1,
            TenantBankAccount::query()
                ->where('tenant_id', $tenant->id)
                ->where('is_primary', true)
                ->count(),
        );
    }

    public function test_it_rejects_setting_another_tenants_account_as_primary(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $service = app(ManageBankAccount::class);

        $accountB = $service->store(
            $tenantB,
            $this->bankData(),
        );

        $this->expectException(ModelNotFoundException::class);

        $service->makePrimary($tenantA, $accountB);
    }

    public function test_it_rejects_invalid_verification_status(): void
    {
        $tenant = Tenant::factory()->create();

        $account = app(ManageBankAccount::class)->store(
            $tenant,
            $this->bankData(),
        );

        $this->expectException(DomainException::class);

        app(ManageBankAccount::class)->verify(
            $tenant,
            $account,
            'pending',
        );
    }
}
