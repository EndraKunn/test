@component('layouts.customer', ['title' => 'Katalog'])

    <h1 class="mb-4 text-xl font-semibold text-slate-900">
        Katalog Menu Kantin
    </h1>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <livewire:catalog::menu-catalog :canteen-slug="$canteen" />
        </div>

        <div id="keranjang" class="lg:sticky lg:top-4 lg:self-start">
            <livewire:ordering::cart :canteen-slug="$canteen" />
        </div>
    </div>

    <livewire:ordering::item-customizer />

@endcomponent
