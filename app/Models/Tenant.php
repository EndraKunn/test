<?php

namespace App\Models;

use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory;

    protected $fillable = [
        'canteen_id',
        'code',
        'slug',
        'display_name',
        'status',
    ];

    /** @return BelongsTo<Canteen, $this> */
    public function canteen(): BelongsTo
    {
        return $this->belongsTo(Canteen::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function menuCategories(): HasMany
    {
        return $this->hasMany(MenuCategory::class);
    }

    public function menus(): HasMany
    {
        return $this->hasMany(Menu::class);
    }

    public function modifierGroups(): HasMany
    {
        return $this->hasMany(ModifierGroup::class);
    }

    public function modifierOptions(): HasMany
    {
        return $this->hasMany(ModifierOption::class);
    }

    /** @return HasOne<TenantBalance, $this> */
    public function balance(): HasOne
    {
        return $this->hasOne(TenantBalance::class);
    }

    public function commissionSchemes(): HasMany
    {
        return $this->hasMany(CommissionScheme::class);
    }

    /** @return HasMany<TenantBankAccount, $this> */
    public function bankAccounts(): HasMany
    {
        return $this->hasMany(TenantBankAccount::class);
    }

    /**
     * @return HasMany<UserTenantRole, $this>
     */
    public function tenantRoles(): HasMany
    {
        return $this->hasMany(UserTenantRole::class);
    }
}
