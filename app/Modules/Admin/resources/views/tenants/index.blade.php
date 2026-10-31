@component('layouts.admin', ['title' => 'Tenant'])
    @if (session('status'))
        <div class="mb-4 rounded-lg bg-green-100 px-4 py-2 text-sm text-green-800">
            {{ session('status') }}
        </div>
    @endif

    <div class="mb-4 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold">
                Tenant — {{ $canteen->name }}
            </h1>

            <p class="mt-1 text-sm text-zinc-500">
                Kelola tenant yang terdaftar pada kantin ini.
            </p>
        </div>

        @can('create', App\Models\Tenant::class)
            <a href="{{ route('admin.tenants.create') }}">
                <x-button>+ Buat Tenant</x-button>
            </a>
        @endcan
    </div>

    <div class="overflow-x-auto rounded-xl border border-zinc-200">
        <table class="w-full min-w-[40rem] text-left text-sm">
            <thead class="bg-zinc-100 text-zinc-600">
                <tr>
                    <th class="px-4 py-2">Nama</th>
                    <th class="px-4 py-2">Kode</th>
                    <th class="px-4 py-2">Slug</th>
                    <th class="px-4 py-2">Status</th>
                    <th class="px-4 py-2 text-right">Aksi</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($tenants as $tenant)
                    <tr class="border-t border-zinc-200">
                        <td class="px-4 py-3 font-medium">
                            {{ $tenant->display_name }}
                        </td>

                        <td class="px-4 py-3 font-mono">
                            {{ $tenant->code }}
                        </td>

                        <td class="px-4 py-3">
                            {{ $tenant->slug }}
                        </td>

                        <td class="px-4 py-3">
                            <x-status-badge :status="$tenant->status">
                                {{ $tenant->status }}
                            </x-status-badge>
                        </td>

                       <td class="px-4 py-3 text-right">
    @can('update', $tenant)
        <div style="display: flex; justify-content: flex-end; align-items: center; gap: 10px; flex-wrap: wrap;">
            <a
                class="text-sm font-medium underline"
                href="{{ route('admin.tenants.edit', $tenant) }}"
            >
                Kelola
            </a>

            <form
                method="POST"
                action="{{ route('admin.tenants.status', $tenant) }}"
                onsubmit="return confirm('Yakin ingin mengubah status tenant ini?')"
            >
                @csrf

                @if ($tenant->status === 'active')
                    <input type="hidden" name="status" value="inactive">

                    <button
                        type="submit"
                        style="padding: 7px 10px; border: 1px solid #dc2626; border-radius: 6px; background: white; color: #dc2626; cursor: pointer;"
                    >
                        Nonaktifkan
                    </button>
                @else
                    <input type="hidden" name="status" value="active">

                    <button
                        type="submit"
                        style="padding: 7px 10px; border: 1px solid #16a34a; border-radius: 6px; background: white; color: #15803d; cursor: pointer;"
                    >
                        Aktifkan
                    </button>
                @endif
            </form>
        </div>
    @endcan
</td>
                    </tr>
                @empty
                    <tr>
                        <td
                            class="px-4 py-6 text-center text-zinc-500"
                            colspan="5"
                        >
                            Belum ada tenant.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endcomponent
