<?php

namespace App\Models\Concerns;

use App\Models\Tenant;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder): void {
            $context = app(TenantContext::class);

            if (! $context->has()) {
                $builder->whereRaw('1 = 0');

                return;
            }

            $builder->where(
                $builder->qualifyColumn('tenant_id'),
                $context->id(),
            );
        });

        static::creating(function (Model $model): void {
            $context = app(TenantContext::class);

            if (! $context->has()) {
                throw new LogicException(
                    'Tenant context wajib diatur sebelum membuat data tenant-owned.',
                );
            }

            $model->setAttribute('tenant_id', $context->id());
        });
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
