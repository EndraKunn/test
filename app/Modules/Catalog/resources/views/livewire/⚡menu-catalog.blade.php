<?php

use App\Models\Canteen;
use App\Modules\Catalog\Services\PublicCatalogQuery;
use App\Modules\Catalog\Services\TenantOpeningHours;
use App\Modules\Kitchen\Services\WaitTimeEstimator;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component
{
    public string $canteenSlug = '';

    #[Url]
    public string $search = '';

    #[Url]
    public ?int $tenantId = null;

    #[Url]
    public ?string $category = null;

    public function mount(string $canteenSlug): void
    {
        $this->canteenSlug = $canteenSlug;
    }

    public function filterCategory(?string $name): void
    {
        $this->category = $name;
    }

    /**
     * Meneruskan permintaan tambah ke komponen keranjang (yang memegang sesi tepercaya).
     */
    public function add(int $menuId): void
    {
        $this->dispatch('cart-add', menuId: $menuId);
    }

    #[Computed]
    public function canteen(): ?Canteen
    {
        return Canteen::query()
            ->where('slug', $this->canteenSlug)
            ->where('status', 'active')
            ->first();
    }

    /**
     * Mengelompokkan seluruh menu berdasarkan tenant.
     * Menu habis tetap ditampilkan.
     */
    #[Computed]
    public function groups(): \Illuminate\Support\Collection
    {
        $canteen = $this->canteen();

        return $canteen
            ? app(PublicCatalogQuery::class)
                ->browseAll(
                    $canteen,
                    $this->search,
                    $this->tenantId,
                    $this->category
                )
                ->groupBy('tenant_id')
            : collect();
    }

    #[Computed]
    public function tenants(): \Illuminate\Support\Collection
    {
        $canteen = $this->canteen();

        return $canteen
            ? app(PublicCatalogQuery::class)->activeTenants($canteen)
            : collect();
    }

    #[Computed]
    public function categories(): \Illuminate\Support\Collection
    {
        $canteen = $this->canteen();

        return $canteen
            ? app(PublicCatalogQuery::class)->categoryNames($canteen)
            : collect();
    }
};
?>

<div class="space-y-4" wire:poll.5s>

    @if (! $this->canteen)

        <x-empty-state
            title="Kantin tidak ditemukan"
            description="Pindai QR meja untuk membuka katalog."
        />

    @else

        @php($hours = app(TenantOpeningHours::class))
        @php($wait = app(WaitTimeEstimator::class))

        {{-- Pencarian dan filter --}}
        <div class="flex flex-col gap-3">

            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Cari menu atau tenant..."
                class="min-h-11 rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 placeholder:text-slate-400 shadow-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-500/20"
            />

            <select
                wire:model.live="tenantId"
                aria-label="Saring tenant"
                class="min-h-11 rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 shadow-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-500/20"
            >
                <option value="">Semua tenant</option>

                @foreach ($this->tenants as $tenant)
                    <option
                        value="{{ $tenant->id }}"
                        wire:key="tenant-{{ $tenant->id }}"
                    >
                        {{ $tenant->display_name }}
                    </option>
                @endforeach
            </select>

            {{-- Chip kategori --}}
            <div
                class="flex gap-2 overflow-x-auto pb-1"
                data-test="category-chips"
            >
                <button
                    type="button"
                    wire:click="filterCategory(null)"
                    @class([
                        'min-h-9 shrink-0 rounded-full border px-3 text-sm',
                        'border-red-600 bg-red-600 text-white' => ! $category,
                        'border-slate-300 bg-white text-slate-700' => $category,
                    ])
                >
                    Semua
                </button>

                @foreach ($this->categories as $name)
                    <button
                        type="button"
                        wire:key="chip-{{ $name }}"
                        wire:click="filterCategory(@js($name))"
                        @class([
                            'min-h-9 shrink-0 rounded-full border px-3 text-sm',
                            'border-red-600 bg-red-600 text-white' => $category === $name,
                            'border-slate-300 bg-white text-slate-700' => $category !== $name,
                        ])
                    >
                        {{ $name }}
                    </button>
                @endforeach
            </div>

        </div>

        <div wire:loading class="text-sm text-slate-500">
            Memuat katalog...
        </div>

        {{-- Daftar menu berdasarkan tenant --}}
        @forelse ($this->groups as $menus)

            @php($tenant = $menus->first()->tenant)
            @php($open = $hours->isOpen($tenant))

            <section
                wire:key="group-{{ $tenant->id }}"
                data-test="tenant-group"
            >

                <div class="flex items-baseline justify-between border-b-2 border-slate-900 pb-2">

                    <h2 class="text-sm font-extrabold uppercase tracking-wide text-slate-900">
                        {{ $tenant->display_name }}
                    </h2>

                    @if ($open)

                        <span class="text-xs font-semibold text-red-600">
                            Antrean {{ $wait->label($tenant) }}
                        </span>

                    @else

                        <span class="text-xs font-semibold text-slate-500">
                            Tutup · Buka pukul
                            {{ $hours->opensAtToday($tenant) ?? '—' }}
                        </span>

                    @endif

                </div>

                <ul class="divide-y divide-slate-200">

                    @foreach ($menus as $menu)

                        @php($sellable = $open && $menu->is_available && $menu->stock_qty > 0)

                        <li
                            wire:key="menu-{{ $menu->id }}"
                            @class([
                                'flex items-center gap-3 py-3',
                                'opacity-50' => ! $sellable,
                            ])
                            data-sellable="{{ $sellable ? '1' : '0' }}"
                        >

                            {{-- Foto --}}
                            @if ($menu->photoUrl())

                                <img
                                    src="{{ $menu->photoUrl() }}"
                                    alt="Foto {{ $menu->name }}"
                                    class="size-14 shrink-0 rounded-lg object-cover"
                                />

                            @else

                                <span class="flex size-14 shrink-0 items-center justify-center rounded-lg bg-slate-200 text-sm font-bold text-slate-700">
                                    {{ Str::upper(Str::substr($menu->name, 0, 2)) }}
                                </span>

                            @endif

                            {{-- Informasi menu --}}
                            <div class="min-w-0 flex-1">

                                <p class="truncate font-semibold text-slate-900">
                                    {{ $menu->name }}
                                </p>

                                <p class="truncate text-xs text-slate-500">
                                    {{ $menu->description ? $menu->description.' · ' : '' }}
                                    ± {{ $menu->prep_minutes }} mnt
                                </p>

                                <p class="text-sm font-semibold text-slate-900">
                                    Rp {{ number_format($menu->base_price, 0, ',', '.') }}
                                </p>

                            </div>

                            {{-- Status stok / Tambah --}}
                            @unless ($menu->is_available && $menu->stock_qty > 0)

                                <span class="shrink-0 rounded border border-slate-300 px-2 py-1 text-[10px] font-bold text-slate-600">
                                    HABIS
                                </span>

                            @endunless

                            @if ($sellable)

                                <button
                                    type="button"
                                    wire:click="add({{ $menu->id }})"
                                    class="min-h-9 shrink-0 cursor-pointer rounded-lg border border-slate-300 bg-white px-3 text-sm font-medium text-slate-800 shadow-sm hover:bg-slate-50"
                                >
                                    + Tambah
                                </button>

                            @endif

                        </li>

                    @endforeach

                </ul>

            </section>

        @empty

            <x-empty-state
                title="Menu tidak ditemukan"
                description="Coba kata kunci atau kategori lain."
            />

        @endforelse

    @endif

</div>
