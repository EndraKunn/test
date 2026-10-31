<?php

namespace App\Modules\Admin\Services;

use App\Models\Tenant;
use App\Models\TenantBankAccount;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class ManageBankAccount
{
    public function __construct(
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Store an encrypted bank account for a tenant.
     *
     * @param array{
     *     bank_code: string,
     *     account_holder: string,
     *     account_number: string
     * } $data
     */
    public function store(
        Tenant $tenant,
        array $data,
    ): TenantBankAccount {
        return DB::transaction(function () use ($tenant, $data): TenantBankAccount {
            $accountNumber = $data['account_number'];

            $account = new TenantBankAccount;

            $account->forceFill([
                'tenant_id' => $tenant->id,
                'bank_code' => $data['bank_code'],
                'account_holder' => $data['account_holder'],
                'account_number_cipher' => $accountNumber,
                'account_last4' => substr($accountNumber, -4),
                'status' => 'unverified',
                'is_primary' => false,
            ]);

            $account->save();

            $this->audit->record(
                'tenant_bank_account',
                $account->id,
                'created',
                null,
                [
                    'bank_code' => $account->bank_code,
                    'account_holder' => $account->account_holder,
                    'account_last4' => $account->account_last4,
                    'status' => $account->status,
                    'is_primary' => $account->is_primary,
                ],
                $tenant->id,
                $tenant->canteen_id,
            );

            return $account;
        });
    }

    public function verify(
        Tenant $tenant,
        TenantBankAccount $account,
        string $status,
    ): TenantBankAccount {
        if (! in_array($status, ['verified', 'rejected'], true)) {
            throw new DomainException('Status verifikasi rekening tidak valid.');
        }

        return DB::transaction(function () use (
            $tenant,
            $account,
            $status,
        ): TenantBankAccount {
            $locked = TenantBankAccount::query()
                ->where('tenant_id', $tenant->id)
                ->whereKey($account->id)
                ->lockForUpdate()
                ->firstOrFail();

            $before = ['status' => $locked->status];

            if ($locked->status === $status) {
                return $locked;
            }

            $locked->forceFill([
                'status' => $status,
                'verified_by' => Auth::id(),
            ])->save();

            $this->audit->record(
                'tenant_bank_account',
                $locked->id,
                $status,
                $before,
                ['status' => $status],
                $tenant->id,
                $tenant->canteen_id,
            );

            return $locked;
        });
    }

    public function makePrimary(
        Tenant $tenant,
        TenantBankAccount $account,
    ): TenantBankAccount {
        return DB::transaction(function () use (
            $tenant,
            $account,
        ): TenantBankAccount {
            $locked = TenantBankAccount::query()
                ->where('tenant_id', $tenant->id)
                ->whereKey($account->id)
                ->lockForUpdate()
                ->firstOrFail();

            $accounts = TenantBankAccount::query()
                ->where('tenant_id', $tenant->id)
                ->lockForUpdate()
                ->get();

            foreach ($accounts as $candidate) {
                if ($candidate->id !== $locked->id && $candidate->is_primary) {
                    $candidate->forceFill([
                        'is_primary' => false,
                    ])->save();
                }
            }

            if (! $locked->is_primary) {
                $locked->forceFill([
                    'is_primary' => true,
                ])->save();

                $this->audit->record(
                    'tenant_bank_account',
                    $locked->id,
                    'primary_set',
                    ['is_primary' => false],
                    [
                        'is_primary' => true,
                        'bank_code' => $locked->bank_code,
                        'account_last4' => $locked->account_last4,
                    ],
                    $tenant->id,
                    $tenant->canteen_id,
                );
            }

            return $locked;
        });
    }
}
