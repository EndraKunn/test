@component('layouts.tenant', ['title' => 'Kelola Modifier — '.$tenant->display_name])

    <div class="mb-4 flex items-baseline justify-between">
        <h1 class="text-xl font-semibold text-slate-900">Kelola Modifier</h1>
        <a href="{{ route('tenant.menu-manager', $tenant) }}" class="text-sm font-medium text-blue-700 underline">
            ← Kelola Menu
        </a>
    </div>

    <livewire:catalog::modifier-manager :tenant-id="$tenant->id" />

@endcomponent
