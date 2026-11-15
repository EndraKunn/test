@props([
    'title' => 'Tidak ada data',
    'description' => null,
    'message' => 'Belum ada data yang tersedia.',
])

<div class="rounded-xl border border-slate-200 bg-white px-5 py-10 text-center">
    <h3 class="text-base font-semibold text-slate-800">
        {{ $title }}
    </h3>

    <p class="mt-1 text-sm text-slate-500">
        {{ $description ?? $message }}
    </p>
</div>