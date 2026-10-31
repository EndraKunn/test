<?php

namespace App\Modules\Admin\Services;

use App\Models\Tenant;
use DomainException;
use Illuminate\Support\Facades\DB;

final class ChangeTenantStatus
{
    public const ALLOWED = ['active', 'inactive'];

    public function __construct(
        private readonly AuditLogger $audit,
    ) {}

    public function handle(Tenant $tenant, string $status): Tenant
    {
        if (! in_array($status, self::ALLOWED, true)) {
            throw new DomainException('Status tenant tidak dikenal.');
        }

        return DB::transaction(function () use ($tenant, $status): Tenant {
            $locked = Tenant::query()
                ->lockForUpdate()
                ->findOrFail($tenant->id);

            $before = $locked->status;

            // Tidak mencatat audit berulang jika status tidak berubah.
            if ($before === $status) {
                return $locked;
            }

            $locked->forceFill([
                'status' => $status,
            ])->save();

            $this->audit->record(
                'tenant',
                $locked->id,
                $status === 'active' ? 'activated' : 'deactivated',
                ['status' => $before],
                ['status' => $status],
                $locked->id,
                $locked->canteen_id,
            );

            return $locked;
        });
    }
}
