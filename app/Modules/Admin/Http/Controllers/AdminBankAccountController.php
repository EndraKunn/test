<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantBankAccount;
use App\Modules\Admin\Http\Requests\StoreBankAccountRequest;
use App\Modules\Admin\Services\ManageBankAccount;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class AdminBankAccountController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ManageBankAccount $service,
    ) {}

    public function store(
        StoreBankAccountRequest $request,
        Tenant $tenant,
    ): RedirectResponse {
        $this->service->store(
            $tenant,
            $request->validated(),
        );

        return redirect()
            ->route('admin.tenants.edit', $tenant)
            ->with('status', 'Rekening bank berhasil ditambahkan.');
    }

    public function verify(
        Request $request,
        Tenant $tenant,
        TenantBankAccount $account,
    ): RedirectResponse {
        $this->authorize('manageBank', $tenant);

        abort_unless(
            (int) $account->tenant_id === (int) $tenant->id,
            404,
        );

        $data = $request->validate([
            'status' => [
                'required',
                Rule::in(['verified', 'rejected']),
            ],
        ]);

        $this->service->verify(
            $tenant,
            $account,
            $data['status'],
        );

        $message = $data['status'] === 'verified'
            ? 'Rekening bank berhasil diverifikasi.'
            : 'Rekening bank ditandai sebagai ditolak.';

        return redirect()
            ->route('admin.tenants.edit', $tenant)
            ->with('status', $message);
    }

    public function makePrimary(
        Tenant $tenant,
        TenantBankAccount $account,
    ): RedirectResponse {
        $this->authorize('manageBank', $tenant);

        abort_unless(
            (int) $account->tenant_id === (int) $tenant->id,
            404,
        );

        $this->service->makePrimary($tenant, $account);

        return redirect()
            ->route('admin.tenants.edit', $tenant)
            ->with('status', 'Rekening utama berhasil diperbarui.');
    }
}
