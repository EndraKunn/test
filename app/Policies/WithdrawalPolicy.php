<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserTenantRole;
use App\Models\Withdrawal;
use App\Support\Tenancy\TenantContext;

class WithdrawalPolicy
{
    /**
     * Determine whether the user can access the active tenant.
     */
    private function canAccessTenant(User $user): bool
    {
        if ($user->status !== 'active') {
            return false;
        }

        $context = app(TenantContext::class);

        if (! $context->has()) {
            return false;
        }

        return UserTenantRole::query()
            ->where('user_id', $user->id)
            ->where('tenant_id', $context->id())
            ->exists();
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Withdrawal $withdrawal): bool
    {
        return $this->canAccessTenant($user)
            && $withdrawal->tenant_id === app(TenantContext::class)->id();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->canAccessTenant($user);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Withdrawal $withdrawal): bool
    {
        return $this->canAccessTenant($user)
            && $withdrawal->tenant_id === app(TenantContext::class)->id();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Withdrawal $withdrawal): bool
    {
        return $this->canAccessTenant($user)
            && $withdrawal->tenant_id === app(TenantContext::class)->id();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Withdrawal $withdrawal): bool
    {
        return $this->canAccessTenant($user)
            && $withdrawal->tenant_id === app(TenantContext::class)->id();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Withdrawal $withdrawal): bool
    {
        return $this->canAccessTenant($user)
            && $withdrawal->tenant_id === app(TenantContext::class)->id();
    }
}
