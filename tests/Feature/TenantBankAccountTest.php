<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantBankAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TenantBankAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_number_is_encrypted_in_database(): void
    {
        $tenant = Tenant::factory()->create();
        $accountNumber = '123456789012';

        $account = new TenantBankAccount;

        $account->forceFill([
            'tenant_id' => $tenant->id,
            'account_number_cipher' => $accountNumber,
            'account_last4' => substr($accountNumber, -4),
            'bank_code' => 'BCA',
            'account_holder' => 'Pemilik Rekening',
            'status' => 'unverified',
            'is_primary' => false,
        ]);

        $account->save();

        $storedValue = DB::table('tenant_bank_accounts')
            ->where('id', $account->id)
            ->value('account_number_cipher');

        $this->assertNotSame($accountNumber, $storedValue);

        $this->assertSame(
            $accountNumber,
            $account->fresh()->account_number_cipher,
        );

        $this->assertSame(
            '9012',
            $account->fresh()->account_last4,
        );
    }

    public function test_primary_flag_is_cast_to_boolean(): void
    {
        $tenant = Tenant::factory()->create();

        $account = new TenantBankAccount;

        $account->forceFill([
            'tenant_id' => $tenant->id,
            'account_number_cipher' => '123456789012',
            'account_last4' => '9012',
            'bank_code' => 'BCA',
            'account_holder' => 'Pemilik Rekening',
            'status' => 'verified',
            'is_primary' => true,
        ]);

        $account->save();

        $this->assertIsBool($account->fresh()->is_primary);
        $this->assertTrue($account->fresh()->is_primary);
    }
}
