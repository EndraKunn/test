<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserTenantRole;
use App\Modules\Admin\Services\AssignTenantRole;
use DomainException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class AdminTenantRoleController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly AssignTenantRole $service,
    ) {}

    public function store(
        Request $request,
        Tenant $tenant,
    ): RedirectResponse {
        $this->authorize('assignRole', $tenant);

        $data = $request->validate([
            'email' => [
                'required',
                'email',
                'max:255',
                'exists:users,email',
            ],
            'role' => [
                'required',
                Rule::in(['owner', 'operator', 'cashier']),
            ],
        ]);

        $user = User::query()
            ->where('email', $data['email'])
            ->firstOrFail();

        $this->service->assign(
            $tenant,
            $user,
            $data['role'],
        );

        return redirect()
            ->route('admin.tenants.edit', $tenant)
            ->with('status', 'Role berhasil ditetapkan.');
    }

    public function destroy(
        Request $request,
        Tenant $tenant,
        User $user,
    ): RedirectResponse {
        $this->authorize('assignRole', $tenant);

        $data = $request->validate([
            'role' => [
                'required',
                Rule::in(['owner', 'operator', 'cashier']),
            ],
        ]);

        $membershipExists = UserTenantRole::query()
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->where('role', $data['role'])
            ->exists();

        abort_unless($membershipExists, 404);

        try {
            $this->service->remove(
                $tenant,
                $user,
                $data['role'],
            );
        } catch (DomainException $exception) {
            return redirect()
                ->route('admin.tenants.edit', $tenant)
                ->withErrors([
                    'role' => $exception->getMessage(),
                ]);
        }

        return redirect()
            ->route('admin.tenants.edit', $tenant)
            ->with('status', 'Role berhasil dicabut.');
    }
}
