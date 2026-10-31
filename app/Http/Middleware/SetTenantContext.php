<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Models\UserTenantRole;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTenantContext
{
    public function handle(
        Request $request,
        Closure $next,
    ): Response {
        $tenantSlug = $request->route('tenant');

        if (! is_string($tenantSlug)) {
            abort(404);
        }

        $tenant = Tenant::query()
            ->where('slug', $tenantSlug)
            ->first();

        if (! $tenant || $tenant->status !== 'active') {
            abort(403);
        }

        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        if ($user->status !== 'active') {
            abort(403);
        }

        $hasRole = UserTenantRole::query()
            ->where('user_id', $user->id)
            ->where('tenant_id', $tenant->id)
            ->exists();

        if (! $hasRole) {
            abort(403);
        }

        $context = app(TenantContext::class);
        $context->set($tenant);

        try {
            return $next($request);
        } finally {
            $context->clear();
        }
    }
}
