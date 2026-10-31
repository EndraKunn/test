<?php

namespace App\Modules\Admin\Services;

use App\Models\Canteen;
use App\Models\CommissionScheme;
use App\Models\Tenant;
use App\Models\TenantBalance;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

final class CreateTenant
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly ManageBankAccount $bankAccounts,
        private readonly AssignTenantRole $roles,
    ) {}

    /** @param array<string, mixed> $data */
    public function handle(Canteen $canteen, array $data): Tenant
    {
        return DB::transaction(function () use ($canteen, $data): Tenant {
            // 1. Membuat tenant.
            $tenant = (new Tenant)->forceFill([
                'canteen_id' => $canteen->id,
                'code' => $data['code'],
                'slug' => $data['slug'],
                'display_name' => $data['display_name'],
                'status' => 'active',
            ]);

            $tenant->save();

            // 2. Membuat saldo awal.
            (new TenantBalance)->forceFill([
                'tenant_id' => $tenant->id,
                'available_amount' => 0,
                'held_amount' => 0,
            ])->save();

            // 3. Membuat skema komisi aktif.
            $commission = (new CommissionScheme)->forceFill([
                'tenant_id' => $tenant->id,
                'commission_rate' => $data['commission_rate'],
                'valid_from' => now(),
                'valid_to' => null,
            ]);

            $commission->save();

            // 4. Mencatat audit tenant.
            $this->audit->record(
                'tenant',
                $tenant->id,
                'created',
                null,
                $tenant->only([
                    'code',
                    'slug',
                    'display_name',
                    'status',
                ]),
                $tenant->id,
                $canteen->id,
            );

            // 5. Mencatat audit skema komisi.
            $this->audit->record(
                'commission_scheme',
                $commission->id,
                'created',
                null,
                $commission->only([
                    'commission_rate',
                    'valid_from',
                ]),
                $tenant->id,
                $canteen->id,
            );

            // 6. Membuat akun owner jika data penanggung jawab tersedia.
            if (isset($data['pic_email'])) {
                $owner = (new User)->forceFill([
                    'name' => $data['pic_name'],
                    'email' => $data['pic_email'],
                    'password' => Str::password(32),
                    'role' => 'tenant',
                    'status' => 'active',
                    'email_verified_at' => now(),
                ]);

                $owner->save();

                $this->roles->assign(
                    $tenant,
                    $owner,
                    'owner',
                );

                $ownerEmail = $owner->email;

                // Email hanya dikirim setelah transaksi berhasil.
                DB::afterCommit(
                    static fn () => Password::broker()->sendResetLink([
                        'email' => $ownerEmail,
                    ]),
                );
            }

            // 7. Menambahkan rekening utama jika data rekening tersedia.
            if (isset($data['account_number'])) {
                $account = $this->bankAccounts->store(
                    $tenant,
                    $data,
                );

                $account->forceFill([
                    'is_primary' => true,
                ])->save();
            }

            return $tenant;
        });
    }
}
