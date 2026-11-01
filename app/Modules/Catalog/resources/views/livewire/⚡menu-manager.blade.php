
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
     * Validasi ulang keanggotaan tenant pada setiap request Livewire.
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
    }

    /**
     * Aturan validasi tambah dan ubah menu.
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
            'name' => 'nama',
            'description' => 'deskripsi',
            'categoryId' => 'kategori',
            'basePrice' => 'harga',
            'prepMinutes' => 'waktu siap',
            'stockQty' => 'stok',
            'photo' => 'foto',
        ];
    }

    /**
     * Membuat menu baru.
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

        // tenant_id otomatis berasal dari TenantContext.
        $menu->save();

        $this->resetForm();

        session()->flash('status', 'Menu dibuat.');
    }

    /**
     * Memuat menu ke form edit.
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
     * Menyimpan perubahan menu.
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

        // Hapus foto lama hanya setelah foto baru berhasil disimpan.
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

        session()->flash('status', 'Menu diperbarui.');
    }

    /**
     * Soft delete menu.
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
            "Menu {$menuName} dihapus."
        );
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

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
     * Menambahkan stok menu.
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

<div class="space-y-6">

    @if (session('status'))
        <div class="rounded-lg bg-green-100 px-4 py-2 text-sm text-green-800 dark:bg-green-900/40 dark:text-green-300">
            {{ session('status') }}
        </div>
    @endif

    {{-- Kategori --}}
    <section>
        <h2 class="font-semibold">Kategori</h2>

        <div class="mt-2 flex flex-wrap items-end gap-2">
            <x-input
                name="newCategory"
                label="Kategori baru"
                wire:model="newCategory"
            />

            <x-button type="button" wire:click="addCategory">
                Tambah Kategori
            </x-button>
        </div>

        <p class="mt-2 text-sm text-zinc-500">
            {{ $this->categories->pluck('name')->join(', ') ?: 'Belum ada kategori.' }}
        </p>
    </section>

    {{-- Form Menu --}}
    <section class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-800">

        <h2 class="font-semibold">
            {{ $editingId ? 'Ubah Menu' : 'Tambah Menu' }}
        </h2>

        <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">

            <div>
                <x-input
                    name="name"
                    label="Nama Menu"
                    wire:model="name"
                />
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    Kategori
                </label>

                <select
                    wire:model="categoryId"
                    class="min-h-11 w-full rounded-lg border border-zinc-300 bg-white px-3 text-sm dark:border-zinc-600 dark:bg-zinc-800"
                >
                    <option value="">Pilih kategori</option>

                    @foreach ($this->categories as $category)
                        <option
                            value="{{ $category->id }}"
                            wire:key="cat-{{ $category->id }}"
                        >
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>

                @error('categoryId')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="sm:col-span-2">
                <x-input
                    name="description"
                    label="Deskripsi (opsional)"
                    wire:model="description"
                />
                @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <x-input
                    name="basePrice"
                    label="Harga (Rp)"
                    type="number"
                    wire:model="basePrice"
                />
                @error('basePrice')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <x-input
                    name="prepMinutes"
                    label="Waktu siap (menit)"
                    type="number"
                    wire:model="prepMinutes"
                />
                @error('prepMinutes')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <x-input
                    name="stockQty"
                    label="Stok"
                    type="number"
                    wire:model="stockQty"
                />
                @error('stockQty')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label
                    for="photo"
                    class="text-sm font-medium text-zinc-700 dark:text-zinc-200"
                >
                    Foto (JPG/PNG/WebP, maksimal 2 MB)
                </label>

                <input
                    id="photo"
                    type="file"
                    wire:model="photo"
                    accept="image/jpeg,image/png,image/webp"
                    class="mt-2 block w-full text-sm"
                />

                @error('photo')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror

                @if ($photo)
                    <img
                        src="{{ $photo->temporaryUrl() }}"
                        alt="Pratinjau foto menu"
                        class="mt-2 size-24 rounded-lg object-cover"
                    />
                @endif
            </div>

        </div>

        <div class="mt-4 flex flex-wrap gap-2">
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
                    Simpan Menu
                </x-button>
            @endif
        </div>

    </section>

    {{-- Daftar Menu --}}
    <section>

        <div class="flex flex-wrap items-end justify-between gap-2">
            <h2 class="font-semibold">Menu &amp; Stok</h2>

            <div class="flex flex-wrap gap-2">

                <input
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Cari menu..."
                    class="min-h-11 rounded-lg border border-zinc-300 bg-white px-3 text-sm dark:border-zinc-600 dark:bg-zinc-800"
                />

                <select
                    wire:model.live="filterCategory"
                    aria-label="Filter kategori"
                    class="min-h-11 rounded-lg border border-zinc-300 bg-white px-3 text-sm dark:border-zinc-600 dark:bg-zinc-800"
                >
                    <option value="">Kategori: Semua</option>

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

        <div class="mt-3 overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-800">

            <table class="w-full min-w-[48rem] text-left text-sm">

                <thead class="bg-zinc-100 text-xs uppercase tracking-wide text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                    <tr>
                        <th class="px-4 py-2">Foto</th>
                        <th class="px-4 py-2">Nama Menu</th>
                        <th class="px-4 py-2">Kategori</th>
                        <th class="px-4 py-2">Harga</th>
                        <th class="px-4 py-2">Siap</th>
                        <th class="px-4 py-2">Stok</th>
                        <th class="px-4 py-2">Status</th>
                        <th class="px-4 py-2 text-right">Aksi</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse ($this->menus as $menu)

                        <tr
                            wire:key="menu-{{ $menu->id }}"
                            class="border-t border-zinc-200 dark:border-zinc-800"
                        >

                            <td class="px-4 py-2">
                                @if ($menu->photoUrl())
                                    <img
                                        src="{{ $menu->photoUrl() }}"
                                        alt="Foto {{ $menu->name }}"
                                        class="size-11 rounded object-cover"
                                    />
                                @else
                                    <span class="flex size-11 items-center justify-center rounded bg-zinc-200 text-xs font-bold text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">
                                        {{ Str::upper(Str::substr($menu->name, 0, 2)) }}
                                    </span>
                                @endif
                            </td>

                            <td class="px-4 py-2 font-medium">
                                {{ $menu->name }}

                                @unless ($menu->is_available && $menu->stock_qty > 0)
                                    <span class="ms-1 rounded bg-red-100 px-1.5 text-[10px] font-bold text-red-700">
                                        HABIS
                                    </span>
                                @endunless

                                @if ($menu->description)
                                    <p class="mt-1 text-xs font-normal text-zinc-500">
                                        {{ $menu->description }}
                                    </p>
                                @endif
                            </td>

                            <td class="px-4 py-2">
                                {{ $menu->category?->name }}
                            </td>

                            <td class="px-4 py-2">
                                Rp {{ number_format($menu->base_price, 0, ',', '.') }}
                            </td>

                            <td class="px-4 py-2">
                                {{ $menu->prep_minutes }} mnt
                            </td>

                            <td class="px-4 py-2 {{ $menu->stock_qty <= 5 ? 'font-semibold text-amber-600' : '' }}">
                                {{ $menu->stock_qty }}
                            </td>

                            <td class="px-4 py-2">
                                <button
                                    type="button"
                                    role="switch"
                                    aria-checked="{{ $menu->is_available ? 'true' : 'false' }}"
                                    wire:click="toggle({{ $menu->id }})"
                                    class="inline-flex min-h-11 items-center gap-2"
                                    aria-label="Ketersediaan {{ $menu->name }}"
                                >
                                    <span class="text-xs">
                                        {{ $menu->is_available ? 'Tersedia' : 'Habis' }}
                                    </span>
                                </button>
                            </td>

                            <td class="px-4 py-2 text-right whitespace-nowrap">

                                <button
                                    type="button"
                                    wire:click="edit({{ $menu->id }})"
                                    class="me-2 min-h-11 text-sm underline"
                                >
                                    Edit
                                </button>

                                <button
                                    type="button"
                                    wire:click="restock({{ $menu->id }}, 10)"
                                    class="me-2 min-h-11 text-sm underline"
                                >
                                    +10 stok
                                </button>

                                <button
                                    type="button"
                                    wire:click="deleteMenu({{ $menu->id }})"
                                    wire:confirm="Hapus menu {{ $menu->name }}? Riwayat pesanan tetap tersimpan."
                                    class="min-h-11 text-sm text-red-700 underline dark:text-red-400"
                                >
                                    Hapus
                                </button>

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td class="px-4 py-3" colspan="8">
                                <x-empty-state title="Belum ada menu" />
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        <p class="mt-2 text-xs text-zinc-500">
            Hapus menggunakan soft delete. Riwayat transaksi tetap dapat merujuk menu yang pernah dipesan.
        </p>

    </section>

</div>
