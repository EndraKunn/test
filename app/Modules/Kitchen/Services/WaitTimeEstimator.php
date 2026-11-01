<?php

namespace App\Modules\Kitchen\Services;

use App\Models\Menu;
use App\Models\OrderItem;
use App\Models\Tenant;
use App\Models\TenantOrder;

/**
 * Menghitung estimasi waktu tunggu pelanggan berdasarkan antrean tenant.
 */
final class WaitTimeEstimator
{
    /**
     * Jumlah pesanan yang dapat dimasak bersamaan oleh satu dapur tenant.
     */
    public const PARALLEL_CAPACITY = 2;

    /**
     * Status pesanan yang masih menunggu atau sedang diproses.
     */
    public const ACTIVE_STATUSES = [
        'accepted',
        'preparing',
    ];

    /**
     * Menghitung total menit antrean aktif tenant.
     */
    public function queueMinutes(Tenant $tenant): float
    {
        $activeOrderIds = TenantOrder::query()
            ->withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->pluck('id');

        if ($activeOrderIds->isEmpty()) {
            return 0.0;
        }

        $totalPrep = (int) OrderItem::query()
            ->withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->whereIn('tenant_order_id', $activeOrderIds)
            ->sum('prep_minutes_snapshot');

        return $totalPrep / self::PARALLEL_CAPACITY;
    }

    /**
     * Menghitung rentang estimasi waktu tunggu.
     *
     * @return array{low: int, high: int}
     */
    public function forTenant(Tenant $tenant, ?float $itemPrep = null): array
    {
        $itemPrep ??= (float) (
            Menu::query()
                ->withoutGlobalScope('tenant')
                ->where('tenant_id', $tenant->id)
                ->where('is_available', true)
                ->avg('prep_minutes') ?? 0
        );

        $estimate = $this->queueMinutes($tenant) + $itemPrep;

        $low = max(1, (int) round($estimate));

        return [
            'low' => $low,
            'high' => max(
                $low + 1,
                (int) ceil($estimate * 1.5)
            ),
        ];
    }

    /**
     * Menghasilkan label estimasi waktu tunggu.
     *
     * Contoh: ± 8–12 mnt
     */
    public function label(Tenant $tenant, ?float $itemPrep = null): string
    {
        $range = $this->forTenant($tenant, $itemPrep);

        return "± {$range['low']}–{$range['high']} mnt";
    }
}
