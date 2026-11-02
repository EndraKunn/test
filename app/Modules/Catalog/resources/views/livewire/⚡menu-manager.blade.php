
<?php

use App\Models\Menu;
use App\Models\MenuCategory;
use App\Models\Tenant;
use App\Models\UserTenantRole;
use App\Modules\Admin\Services\AuditLogger;
use App\Modules\Catalog\Services\MenuPhotoStore;
use App\Modules\Catalog\Services\MenuStockService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public ?int $editingId = null;

    public string $description = '';

    public $photo = null;

    public string $search = '';

    public ?int $filterCategory = null;

    public int $tenantId = 0;

    public string $name = '';

    public ?int $categoryId = null;

    public int $basePrice = 0;

    public int $prepMinutes = 10;

    public int $stockQty = 0;

    public string $newCategory = '';

    public function mount(int $tenantId): void
    {
        $this->tenantId = $tenantId;
    }

    /**
     * Verifikasi keanggotaan tenant pada setiap request Livewire.
     */
    public function booted(): void
    {
        $user = Auth::user();

        $isMember = $user !== null && UserTenantRole::query()
            ->where('user_id', $user->id)
            ->where('tenant_id', $this->tenantId)
            ->exists();

        abort_unless($isMember, 403);

        app(TenantContext::class)->set(
            Tenant::query()->findOrFail($this->tenantId)
        );
    }

    #[Computed]
    public function menus(): \Illuminate\Database\Eloquent\Collection
    {
        return Menu::query()
            ->with('category:id,name')
            ->when(
                $this->search !== '',
                fn ($query) => $query->where(
                    'name',
                    'like',
                    '%'.$this->search.'%'
                )
            )
            ->when(
                $this->filterCategory !== null,
                fn ($query) => $query->where(
                    'category_id',
                    $this->filterCategory
                )
            )
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function categories(): \Illuminate\Database\Eloquent\Collection
    {
        return MenuCategory::query()
            ->orderBy('name')
            ->get();
    }

    /**
     * Menambahkan kategori baru.
     */
    public function addCategory(): void
    {
        $data = $this->validate([
            'newCategory' => ['required', 'string', 'max:120'],
        ]);

        MenuCategory::create([
            'name' => $data['newCategory'],
        ]);

        $this->newCategory = '';

        unset($this->categories);

        session()->flash('status', 'Kategori berhasil ditambahkan.');
    }

    /**
     * Aturan validasi menu.
     */
    private function menuRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'categoryId' => ['required', 'integer'],
            'basePrice' => ['required', 'integer', 'min:1'],
            'prepMinutes' => ['required', 'integer', 'min:1', 'max:240'],
            'stockQty' => ['required', 'integer', 'min:0'],
            'photo' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'name' => 'nama menu',
            'description' => 'deskripsi',
            'categoryId' => 'kategori',
            'basePrice' => 'harga',
            'prepMinutes' => 'waktu persiapan',
            'stockQty' => 'stok',
            'photo' => 'foto',
        ];
    }

    /**
     * Membuat menu.
     */
    public function createMenu(): void
    {
        $data = $this->validate($this->menuRules());

        // Kategori harus berasal dari tenant aktif.
        $category = MenuCategory::query()
            ->findOrFail($data['categoryId']);

        $photoPath = $this->photo
            ? app(MenuPhotoStore::class)->store(
                $this->photo,
                $this->tenantId
            )
            : null;

        $menu = new Menu;

        $menu->forceFill([
            'category_id' => $category->id,
            'name' => $data['name'],
            'description' => $data['description'] ?: null,
            'base_price' => $data['basePrice'],
            'prep_minutes' => $data['prepMinutes'],
            'stock_qty' => $data['stockQty'],
            'is_available' => true,
            'photo_path' => $photoPath,
        ]);

        // tenant_id berasal dari TenantContext.
        $menu->save();

        $this->resetForm();

        session()->flash('status', 'Menu berhasil ditambahkan.');
    }

    /**
     * Memuat data menu untuk diedit.
     */
    public function edit(int $menuId): void
    {
        $menu = Menu::query()->findOrFail($menuId);

        $this->editingId = $menu->id;
        $this->name = $menu->name;
        $this->description = (string) $menu->description;
        $this->categoryId = $menu->category_id;
        $this->basePrice = $menu->base_price;
        $this->prepMinutes = $menu->prep_minutes;
        $this->stockQty = $menu->stock_qty;
        $this->photo = null;
    }

    /**
     * Memperbarui menu.
     */
    public function updateMenu(): void
    {
        $data = $this->validate($this->menuRules());

        $menu = Menu::query()->findOrFail((int) $this->editingId);

        $category = MenuCategory::query()
            ->findOrFail($data['categoryId']);

        $before = $menu->only([
            'name',
            'description',
            'base_price',
            'prep_minutes',
            'category_id',
        ]);

        $oldPhotoPath = $menu->photo_path;

        $newPhotoPath = $this->photo
            ? app(MenuPhotoStore::class)->store(
                $this->photo,
                $this->tenantId
            )
            : $oldPhotoPath;

        $menu->forceFill([
            'category_id' => $category->id,
            'name' => $data['name'],
            'description' => $data['description'] ?: null,
            'base_price' => $data['basePrice'],
            'prep_minutes' => $data['prepMinutes'],
            'stock_qty' => $data['stockQty'],
            'photo_path' => $newPhotoPath,
        ])->save();

        if ($this->photo && $oldPhotoPath !== null) {
            app(MenuPhotoStore::class)->delete($oldPhotoPath);
        }

        app(AuditLogger::class)->record(
            'menu',
            $menu->id,
            'updated',
            $before,
            $menu->only([
                'name',
                'description',
                'base_price',
                'prep_minutes',
                'category_id',
            ]),
            $this->tenantId
        );

        $this->resetForm();

        session()->flash('status', 'Menu berhasil diperbarui.');
    }

    /**
     * Menghapus menu menggunakan soft delete.
     */
    public function deleteMenu(int $menuId): void
    {
        $menu = Menu::query()->findOrFail($menuId);

        $menuName = $menu->name;

        $menu->delete();

        app(AuditLogger::class)->record(
            'menu',
            $menu->id,
            'deleted',
            ['name' => $menuName],
            null,
            $this->tenantId
        );

        unset($this->menus);

        session()->flash(
            'status',
            "Menu {$menuName} berhasil dihapus."
        );
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    /**
     * Mengembalikan form ke kondisi awal.
     */
    private function resetForm(): void
    {
        $this->reset([
            'name',
            'description',
            'basePrice',
            'prepMinutes',
            'stockQty',
            'categoryId',
            'photo',
            'editingId',
        ]);

        $this->resetValidation();

        unset($this->menus);
    }

    /**
     * Mengubah ketersediaan menu.
     */
    public function toggle(int $menuId): void
    {
        $menu = Menu::query()->findOrFail($menuId);

        app(MenuStockService::class)
            ->toggleAvailability($menu);

        unset($this->menus);
    }

    /**
     * Menambahkan stok.
     */
    public function restock(int $menuId, int $delta): void
    {
        $menu = Menu::query()->findOrFail($menuId);

        app(MenuStockService::class)->adjust(
            $menu,
            $delta,
            'restock',
            (string) Str::uuid(),
            'manual restock'
        );

        unset($this->menus);
    }

};
?>

<div class="space-y-8">

    {{-- Notifikasi --}}
    @if (session('status'))
        <div class="flex items-center gap-3 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">
            <span class="flex size-7 items-center justify-center rounded-full bg-green-100">
                ✓
            </span>

            {{ session('status') }}
        </div>
    @endif

    {{-- Kategori --}}
    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

        <div class="mb-4">
            <h2 class="text-lg font-bold text-slate-800">
                Kategori Menu
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Tambahkan kategori untuk mengelompokkan menu kantin.
            </p>
        </div>

        <form wire:submit="addCategory" class="flex flex-col gap-3 sm:flex-row sm:items-end">

            <div class="flex-1">
                <x-input
                    name="newCategory"
                    label="Nama kategori"
                    placeholder="Contoh: Makanan, Minuman, Camilan"
                    wire:model="newCategory"
                />

                @error('newCategory')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <x-button type="submit">
                + Tambah Kategori
            </x-button>

        </form>

        <div class="mt-4 flex flex-wrap gap-2">
            @forelse ($this->categories as $category)
                <span class="rounded-full bg-blue-50 px-3 py-1.5 text-sm font-medium text-blue-700">
                    {{ $category->name }}
                </span>
            @empty
                <p class="text-sm text-slate-500">
                    Belum ada kategori.
                </p>
            @endforelse
        </div>

    </section>

    {{-- Form Menu --}}
    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

        <div class="mb-5 flex items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold text-slate-800">
                    {{ $editingId ? 'Edit Menu' : 'Tambah Menu Baru' }}
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Lengkapi informasi menu yang akan ditampilkan.
                </p>
            </div>

            @if ($editingId)
                <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">
                    Mode Edit
                </span>
            @endif
        </div>

        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

            {{-- Nama --}}
            <div>
                <x-input
                    name="name"
                    label="Nama Menu"
                    placeholder="Contoh: Nasi Goreng Spesial"
                    wire:model="name"
                />

                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Kategori --}}
            <div>
                <label
                    for="categoryId"
                    class="mb-1.5 block text-sm font-semibold text-slate-700"
                >
                    Kategori Menu
                </label>

                <select
                    id="categoryId"
                    wire:model="categoryId"
                    class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                >
                    <option value="">Pilih kategori</option>

                    @foreach ($this->categories as $category)
                        <option
                            value="{{ $category->id }}"
                            wire:key="category-{{ $category->id }}"
                        >
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>

                @error('categoryId')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Deskripsi --}}
            <div class="md:col-span-2">
                <x-input
                    name="description"
                    label="Deskripsi Menu"
                    placeholder="Tuliskan informasi singkat mengenai menu..."
                    wire:model="description"
                />

                @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Harga --}}
            <div>
                <x-input
                    name="basePrice"
                    label="Harga (Rupiah)"
                    type="number"
                    placeholder="Contoh: 15000"
                    wire:model="basePrice"
                />

                <p class="mt-1 text-xs text-slate-500">
                    Harga minimal Rp1.
                </p>

                @error('basePrice')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Waktu Siap --}}
            <div>
                <x-input
                    name="prepMinutes"
                    label="Waktu Persiapan (Menit)"
                    type="number"
                    placeholder="Contoh: 10"
                    wire:model="prepMinutes"
                />

                @error('prepMinutes')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Stok --}}
            <div>
                <x-input
                    name="stockQty"
                    label="Jumlah Stok"
                    type="number"
                    placeholder="Contoh: 50"
                    wire:model="stockQty"
                />

                @error('stockQty')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Foto --}}
            <div>
                <label
                    for="photo"
                    class="mb-1.5 block text-sm font-semibold text-slate-700"
                >
                    Foto Menu
                </label>

                <input
                    id="photo"
                    type="file"
                    wire:model="photo"
                    accept="image/jpeg,image/png,image/webp"
                    class="block min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 file:me-3 file:rounded-md file:border-0 file:bg-blue-50 file:px-3 file:py-1 file:text-sm file:font-semibold file:text-blue-700 hover:file:bg-blue-100"
                />

                <p class="mt-1 text-xs text-slate-500">
                    JPG, PNG, atau WebP. Maksimal 2 MB.
                    Foto akan dikompres menjadi WebP.
                </p>

                @error('photo')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror

                @if ($photo)
                    <div class="mt-3">
                        <p class="mb-2 text-xs font-semibold text-slate-600">
                            Pratinjau foto baru
                        </p>

                        <img
                            src="{{ $photo->temporaryUrl() }}"
                            alt="Pratinjau foto menu"
                            class="size-28 rounded-xl border border-slate-200 object-cover"
                        />
                    </div>
                @endif
            </div>

        </div>

        {{-- Tombol Form --}}
        <div class="mt-6 flex flex-wrap gap-3 border-t border-slate-100 pt-5">

            @if ($editingId)
                <x-button type="button" wire:click="updateMenu">
                    Simpan Perubahan
                </x-button>

                <x-button
                    type="button"
                    variant="secondary"
                    wire:click="cancelEdit"
                >
                    Batal
                </x-button>
            @else
                <x-button type="button" wire:click="createMenu">
                    + Simpan Menu
                </x-button>
            @endif

        </div>

    </section>

    {{-- Daftar Menu --}}
    <section class="space-y-4">

        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">

            <div>
                <h2 class="text-lg font-bold text-slate-800">
                    Daftar Menu &amp; Stok
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Pantau ketersediaan, harga, dan stok menu.
                </p>
            </div>

            <div class="flex flex-col gap-2 sm:flex-row">

                <input
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Cari nama menu..."
                    aria-label="Cari menu"
                    class="min-h-11 rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                />

                <select
                    wire:model.live="filterCategory"
                    aria-label="Filter kategori"
                    class="min-h-11 rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                >
                    <option value="">Semua Kategori</option>

                    @foreach ($this->categories as $category)
                        <option
                            value="{{ $category->id }}"
                            wire:key="filter-{{ $category->id }}"
                        >
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>

            </div>

        </div>

        {{-- Tabel --}}
        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">

            <table class="w-full min-w-[850px] text-left text-sm">

                <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wide text-slate-600">
                    <tr>
                        <th class="px-4 py-4">Foto</th>
                        <th class="px-4 py-4">Nama Menu</th>
                        <th class="px-4 py-4">Kategori</th>
                        <th class="px-4 py-4">Harga</th>
                        <th class="px-4 py-4">Waktu Siap</th>
                        <th class="px-4 py-4">Stok</th>
                        <th class="px-4 py-4">Status</th>
                        <th class="px-4 py-4 text-right">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">

                    @forelse ($this->menus as $menu)

                        @php
                            $sellable = $menu->is_available && $menu->stock_qty > 0;
                        @endphp

                        <tr
                            wire:key="menu-{{ $menu->id }}"
                            class="transition hover:bg-slate-50"
                        >

                            {{-- Foto --}}
                            <td class="px-4 py-3">
                                @if ($menu->photoUrl())
                                    <img
                                        src="{{ $menu->photoUrl() }}"
                                        alt="Foto {{ $menu->name }}"
                                        class="size-12 rounded-lg border border-slate-200 object-cover"
                                    />
                                @else
                                    <span class="flex size-12 items-center justify-center rounded-lg bg-blue-50 text-sm font-bold text-blue-700">
                                        {{ Str::upper(Str::substr($menu->name, 0, 2)) }}
                                    </span>
                                @endif
                            </td>

                            {{-- Nama Menu --}}
                            <td class="px-4 py-3">
                                <p class="font-semibold text-slate-800">
                                    {{ $menu->name }}
                                </p>

                                @if ($menu->description)
                                    <p class="mt-1 max-w-48 text-xs text-slate-500">
                                        {{ $menu->description }}
                                    </p>
                                @endif

                                @unless ($sellable)
                                    <span class="mt-1 inline-flex rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-bold text-red-700">
                                        HABIS
                                    </span>
                                @endunless
                            </td>

                            {{-- Kategori --}}
                            <td class="px-4 py-3 text-slate-600">
                                {{ $menu->category?->name }}
                            </td>

                            {{-- Harga --}}
                            <td class="px-4 py-3 font-semibold text-slate-800">
                                Rp {{ number_format($menu->base_price, 0, ',', '.') }}
                            </td>

                            {{-- Waktu Persiapan --}}
                            <td class="px-4 py-3 text-slate-600">
                                {{ $menu->prep_minutes }} menit
                            </td>

                            {{-- Stok --}}
                            <td class="px-4 py-3">
                                <span class="{{ $menu->stock_qty <= 5 ? 'font-bold text-amber-600' : 'font-medium text-slate-700' }}">
                                    {{ $menu->stock_qty }}
                                </span>
                            </td>

                            {{-- Status --}}
                            <td class="px-4 py-3">

                                <button
                                    type="button"
                                    role="switch"
                                    aria-checked="{{ $menu->is_available ? 'true' : 'false' }}"
                                    wire:click="toggle({{ $menu->id }})"
                                    class="inline-flex min-h-10 items-center gap-2 rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                    aria-label="Ubah ketersediaan {{ $menu->name }}"
                                >
                                    <span class="relative h-6 w-11 rounded-full transition {{ $menu->is_available ? 'bg-green-500' : 'bg-slate-300' }}">
                                        <span class="absolute top-0.5 size-5 rounded-full bg-white shadow transition {{ $menu->is_available ? 'left-5' : 'left-0.5' }}"></span>
                                    </span>
                                </button>

                                <p class="mt-1 text-xs font-medium {{ $sellable ? 'text-green-700' : 'text-red-600' }}">
                                    {{ $sellable ? 'Tersedia' : 'Habis' }}
                                </p>

                            </td>

                            {{-- Aksi --}}
                            <td class="px-4 py-3 text-right whitespace-nowrap">

                                <button
                                    type="button"
                                    wire:click="edit({{ $menu->id }})"
                                    class="me-2 rounded-md px-2 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-50"
                                >
                                    Edit
                                </button>

                                <button
                                    type="button"
                                    wire:click="restock({{ $menu->id }}, 10)"
                                    class="me-2 rounded-md px-2 py-2 text-xs font-semibold text-green-700 hover:bg-green-50"
                                >
                                    +10 Stok
                                </button>

                                <button
                                    type="button"
                                    wire:click="deleteMenu({{ $menu->id }})"
                                    wire:confirm="Hapus menu {{ $menu->name }}? Riwayat pesanan tetap tersimpan."
                                    class="rounded-md px-2 py-2 text-xs font-semibold text-red-600 hover:bg-red-50"
                                >
                                    Hapus
                                </button>

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="8" class="px-4 py-10 text-center">
                                <x-empty-state
                                    title="Menu tidak ditemukan"
                                    description="Tambahkan menu baru atau ubah kata kunci pencarian."
                                />
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        <p class="text-xs text-slate-500">
            Informasi stok dan ketersediaan diperbarui secara langsung.
            Penghapusan menggunakan soft delete sehingga data menu tetap tersimpan dalam riwayat transaksi.
        </p>

    </section>

</div>
