<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\ScheduleCommissionRequest;
use App\Models\Tenant;
use App\Modules\Admin\Services\ChangeCommissionSchedule;
use Carbon\Carbon;
use DomainException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;

final class AdminCommissionController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ChangeCommissionSchedule $service,
    ) {}

    public function store(
        ScheduleCommissionRequest $request,
        Tenant $tenant,
    ): RedirectResponse {
        $data = $request->validated();

        try {
            $this->service->handle(
                $tenant,
                (string) ((float) $data['commission_rate'] / 100),
                Carbon::parse($data['effective_at']),
            );
        } catch (DomainException $exception) {
            return redirect()
                ->route('admin.tenants.edit', $tenant)
                ->withErrors([
                    'commission_rate' => $exception->getMessage(),
                ])
                ->withInput();
        }

        return redirect()
            ->route('admin.tenants.edit', $tenant)
            ->with(
                'status',
                'Skema komisi baru berhasil dijadwalkan.',
            );
    }
}
