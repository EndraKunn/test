@component('layouts.tenant', ['title' => 'Kelola Menu — '.$tenant->display_name])

    <div class="mb-4 flex items-baseline justify-between">
        <h1 class="text-xl font-semibold text-slate-900">Kelola Menu</h1>
        <a href="{{ route('tenant.modifier-manager', $tenant) }}" class="text-sm font-medium text-blue-700 underline">
            Kelola Modifier →
        </a>
    </div>

    <livewire:catalog::menu-manager :tenant-id="$tenant->id" />

@endcomponent
