
@component('layouts.admin', ['title' => 'Meja & QR'])
    @if (session('status'))
        <div class="mb-4 rounded-lg bg-green-100 px-4 py-2 text-sm text-green-800">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-red-100 px-4 py-2 text-sm text-red-800">
            <ul class="list-disc ps-4">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mb-6">
        <h1 class="text-xl font-semibold">
            Meja & QR — {{ $canteen->name }}
        </h1>

        <p class="mt-1 text-sm text-zinc-500">
            Kelola meja, lokasi, dan QR Code untuk kantin ini.
        </p>
    </div>

    <section class="rounded-xl border border-zinc-200 bg-white p-5">
        <h2 class="font-semibold">Tambah Meja</h2>

        <form
            method="POST"
            action="{{ route('admin.tables.store') }}"
            class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4"
        >
            @csrf

            <div>
                <label for="code" class="mb-1 block text-sm font-medium">
                    Kode Meja
                </label>

                <input
                    id="code"
                    name="code"
                    type="text"
                    value="{{ old('code') }}"
                    maxlength="30"
                    required
                    class="w-full rounded-lg border border-zinc-300 px-3 py-2"
                    placeholder="Contoh: MEJA-01"
                >

                @error('code')
                    <p class="mt-1 text-sm text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div>
                <label for="label" class="mb-1 block text-sm font-medium">
                    Label Meja
                </label>

                <input
                    id="label"
                    name="label"
                    type="text"
                    value="{{ old('label') }}"
                    maxlength="60"
                    required
                    class="w-full rounded-lg border border-zinc-300 px-3 py-2"
                    placeholder="Contoh: Meja dekat jendela"
                >

                @error('label')
                    <p class="mt-1 text-sm text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div>
                <label for="zone" class="mb-1 block text-sm font-medium">
                    Zona
                </label>

                <input
                    id="zone"
                    name="zone"
                    type="text"
                    value="{{ old('zone') }}"
                    maxlength="60"
                    class="w-full rounded-lg border border-zinc-300 px-3 py-2"
                    placeholder="Contoh: Zona A"
                >

                @error('zone')
                    <p class="mt-1 text-sm text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div class="flex items-end">
                <button
                    type="submit"
                    class="w-full rounded-lg bg-zinc-900 px-4 py-2 font-medium text-white hover:bg-zinc-700"
                >
                    + Tambah Meja
                </button>
            </div>
        </form>
    </section>

    <section class="mt-6">
        <h2 class="mb-3 font-semibold">Daftar Meja</h2>

        <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white">
            <table class="w-full min-w-[48rem] text-left text-sm">
                <thead class="bg-zinc-100 text-zinc-600">
                    <tr>
                        <th class="px-4 py-3">Kode</th>
                        <th class="px-4 py-3">Label</th>
                        <th class="px-4 py-3">Zona</th>
                        <th class="px-4 py-3">Status Meja</th>
                        <th class="px-4 py-3">Status QR</th>
                        <th class="px-4 py-3">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($tables as $table)
                        <tr class="border-t border-zinc-200">
                            <td class="px-4 py-3 font-mono">
                                {{ $table->code }}
                            </td>

                            <td class="px-4 py-3">
                                {{ $table->label }}
                            </td>

                            <td class="px-4 py-3">
                                {{ $table->zone ?? '—' }}
                            </td>

                            <td class="px-4 py-3">
                                @if ($table->isActive())
                                    <span class="rounded-full bg-green-100 px-2 py-1 text-xs text-green-800">
                                        Aktif
                                    </span>
                                @else
                                    <span class="rounded-full bg-red-100 px-2 py-1 text-xs text-red-800">
                                        Nonaktif
                                    </span>
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                @if (! $table->isActive())
                                    <span class="rounded-full bg-zinc-100 px-2 py-1 text-xs text-zinc-600">
                                        Meja nonaktif
                                    </span>
                                @elseif ($table->activeToken && $table->activeToken->isActive())
                                    <span class="rounded-full bg-green-100 px-2 py-1 text-xs text-green-800">
                                        Aktif
                                    </span>
                                @else
                                    <span class="rounded-full bg-zinc-100 px-2 py-1 text-xs text-zinc-600">
                                        Belum aktif
                                    </span>
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-2">
                                    @if ($table->isActive())
                                        <form
                                            method="POST"
                                            action="{{ route('admin.tables.rotate', $table) }}"
                                        >
                                            @csrf

                                            <button
                                                type="submit"
                                                class="rounded-lg border border-zinc-300 px-3 py-2 text-sm font-medium hover:bg-zinc-100"
                                            >
                                                {{ $table->activeToken && $table->activeToken->isActive()
                                                    ? 'Rotate QR'
                                                    : 'Terbitkan QR' }}
                                            </button>
                                        </form>
                                    @endif

                                    <form
                                        method="POST"
                                        action="{{ route('admin.tables.status', $table) }}"
                                    >
                                        @csrf

                                        <input
                                            type="hidden"
                                            name="status"
                                            value="{{ $table->isActive() ? 'inactive' : 'active' }}"
                                        >

                                        <button
                                            type="submit"
                                            class="rounded-lg border px-3 py-2 text-sm font-medium {{ $table->isActive()
                                                ? 'border-red-300 text-red-700 hover:bg-red-50'
                                                : 'border-green-300 text-green-700 hover:bg-green-50' }}"
                                        >
                                            {{ $table->isActive() ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-zinc-500">
                                Belum ada meja. Tambahkan meja untuk mulai menerbitkan QR.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endcomponent
