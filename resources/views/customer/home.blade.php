
@component('layouts.customer', ['title' => 'Katalog'])

    <h1 class="mb-4 text-xl font-semibold">
        Katalog Menu Kantin
    </h1>

    <livewire:catalog::menu-catalog :canteen-slug="$canteen" />

@endcomponent
