<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Modules\Admin\Services\ChangeTenantStatus;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class AdminTenantStatusController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ChangeTenantStatus $service,
    ) {}

    public function __invoke(
        Request $request,
        Tenant $tenant,
    ): RedirectResponse {
        $this->authorize('update', $tenant);

        $data = $request->validate([
            'status' => [
                'required',
                Rule::in(ChangeTenantStatus::ALLOWED),
            ],
        ]);

        $this->service->handle(
            $tenant,
            $data['status'],
        );

        $message = $data['status'] === 'active'
            ? "Tenant {$tenant->display_name} diaktifkan."
            : "Tenant {$tenant->display_name} dinonaktifkan dan disembunyikan dari katalog.";

        return redirect()
            ->route('admin.tenants.index')
            ->with('status', $message);
    }
}
