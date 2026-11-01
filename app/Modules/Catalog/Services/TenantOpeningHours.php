<?php

namespace App\Modules\Catalog\Services;

use App\Models\Tenant;
use App\Models\TenantOperatingHour;

final class TenantOpeningHours
{
    /**
     * Mengambil jadwal operasional tenant untuk hari ini.
     */
    private function scheduleForToday(Tenant $tenant): ?TenantOperatingHour
    {
        return TenantOperatingHour::query()
            ->withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->where('day_of_week', now()->dayOfWeek)
            ->first();
    }

    /**
     * Memeriksa apakah tenant sedang buka.
     */
    public function isOpen(Tenant $tenant): bool
    {
        $hasSchedule = TenantOperatingHour::query()
            ->withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->exists();

        // Jika tenant belum mengatur jam operasional,
        // anggap tenant buka secara default.
        if (! $hasSchedule) {
            return true;
        }

        $schedule = $this->scheduleForToday($tenant);

        if ($schedule === null) {
            return false;
        }

        $currentTime = now()->format('H:i:s');

        return $currentTime >= $schedule->opens_at
            && $currentTime < $schedule->closes_at;
    }

    /**
     * Mengambil jam buka tenant hari ini.
     *
     * Contoh: 10.00
     */
    public function opensAtToday(Tenant $tenant): ?string
    {
        $schedule = $this->scheduleForToday($tenant);

        if ($schedule === null) {
            return null;
        }

        return str_replace(
            ':',
            '.',
            substr($schedule->opens_at, 0, 5)
        );
    }
}
