<?php

namespace App\Modules\Catalog\Services;

use App\Models\Canteen;
use App\Models\Menu;
use App\Models\MenuCategory;
use App\Models\Tenant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

/**
 * Query lintas-tenant yang sah untuk katalog publik satu kantin.
 */
final class PublicCatalogQuery
{
    /**
     * Menu tersedia milik tenant aktif dalam satu kantin.
     *
     * @return Collection<int, Menu>
     */
    public function forCanteen(Canteen $canteen): Collection
    {
        return Menu::query()
            ->withoutGlobalScope('tenant')
            ->where('is_available', true)
            ->whereHas('tenant', function ($query) use ($canteen): void {
                $query->where('canteen_id', $canteen->id)
                    ->where('status', 'active');
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * Katalog publik dengan pagination, pencarian,
     * dan filter tenant. Dipertahankan untuk kompatibilitas Tahap 7.
     *
     * @return LengthAwarePaginator<int, Menu>
     */
    public function browse(
        Canteen $canteen,
        string $search = '',
        ?int $tenantId = null,
    ): LengthAwarePaginator {
        return Menu::query()
            ->withoutGlobalScope('tenant')
            ->sellable()
            ->whereHas('tenant', function ($query) use ($canteen): void {
                $query->where('canteen_id', $canteen->id)
                    ->where('status', 'active');
            })
            ->when(
                $tenantId !== null,
                fn ($query) => $query->where('tenant_id', $tenantId)
            )
            ->when(
                $search !== '',
                fn ($query) => $query->where('name', 'like', '%'.$search.'%')
            )
            ->with([
                'tenant:id,display_name',
                'category:id,name',
            ])
            ->orderBy('name')
            ->paginate(16)
            ->withQueryString();
    }

    /**
     * Katalog seluruh menu termasuk yang habis.
     * Mendukung pencarian menu, deskripsi, tenant, dan kategori.
     *
     * @return Collection<int, Menu>
     */
    public function browseAll(
        Canteen $canteen,
        string $search = '',
        ?int $tenantId = null,
        ?string $category = null,
    ): Collection {
        return Menu::query()
            ->withoutGlobalScope('tenant')
            ->whereHas('tenant', function ($query) use ($canteen): void {
                $query->where('canteen_id', $canteen->id)
                    ->where('status', 'active');
            })
            ->when(
                $tenantId !== null,
                fn ($query) => $query->where('tenant_id', $tenantId)
            )
            ->when(
                $category !== null && $category !== '',
                fn ($query) => $query->whereHas(
                    'category',
                    fn ($query) => $query
                        ->withoutGlobalScope('tenant')
                        ->where('name', $category)
                )
            )
            ->when($search !== '', function ($query) use ($search): void {
                $like = '%'.$search.'%';

                $query->where(function ($query) use ($like): void {
                    $query->where('name', 'like', $like)
                        ->orWhere('description', 'like', $like)
                        ->orWhereHas(
                            'tenant',
                            fn ($tenantQuery) => $tenantQuery
                                ->where('display_name', 'like', $like)
                        )
                        ->orWhereHas(
                            'category',
                            fn ($categoryQuery) => $categoryQuery
                                ->withoutGlobalScope('tenant')
                                ->where('name', 'like', $like)
                        );
                });
            })
            ->with([
                'tenant:id,display_name,slug',
                'category' => fn ($query) => $query
                    ->withoutGlobalScope('tenant')
                    ->select('id', 'name'),
            ])
            ->orderBy('tenant_id')
            ->orderBy('name')
            ->get();
    }

    /**
     * Nama kategori unik dari seluruh tenant aktif dalam satu kantin.
     *
     * @return SupportCollection<int, string>
     */
    public function categoryNames(Canteen $canteen): SupportCollection
    {
        return MenuCategory::query()
            ->withoutGlobalScope('tenant')
            ->whereHas('tenant', function ($query) use ($canteen): void {
                $query->where('canteen_id', $canteen->id)
                    ->where('status', 'active');
            })
            ->orderBy('name')
            ->distinct()
            ->pluck('name');
    }

    /**
     * Mengambil seluruh tenant aktif milik kantin.
     *
     * @return SupportCollection<int, Tenant>
     */
    public function activeTenants(Canteen $canteen): SupportCollection
    {
        return Tenant::query()
            ->where('canteen_id', $canteen->id)
            ->where('status', 'active')
            ->orderBy('display_name')
            ->get(['id', 'display_name']);
    }
}
