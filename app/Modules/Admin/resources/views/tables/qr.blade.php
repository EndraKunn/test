
@component('layouts.admin', ['title' => 'QR Meja '.$table->code])
    <div class="mb-6">
        <h1 class="text-xl font-semibold">
            QR — {{ $table->label }}
        </h1>

        <p class="mt-1 text-sm text-zinc-500">
            Kode meja: {{ $table->code }}
            @if ($table->zone)
                · Zona {{ $table->zone }}
            @endif
        </p>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-lg bg-green-100 px-4 py-2 text-sm text-green-800">
            {{ session('status') }}
        </div>
    @endif

    @if ($url && $svg)
        <section class="rounded-xl border border-amber-300 bg-amber-50 p-5">
            <h2 class="font-semibold text-amber-900">
                Token QR baru berhasil diterbitkan
            </h2>

            <p class="mt-2 text-sm text-amber-800">
                Token mentah hanya ditampilkan setelah penerbitan.
                Simpan atau unduh QR ini sekarang.
            </p>

            <div class="mt-4 flex justify-center rounded-xl border border-amber-200 bg-white p-5">
                <div class="w-full max-w-xs [&_svg]:h-auto [&_svg]:w-full">
                    {!! $svg !!}
                </div>
            </div>

            <div class="mt-4">
                <p class="mb-2 text-sm font-medium text-zinc-700">
                    URL QR
                </p>

                <p class="break-all rounded-lg border border-amber-200 bg-white p-3 font-mono text-sm text-zinc-900">
                    {{ $url }}
                </p>
            </div>

            <div class="mt-4 flex flex-wrap gap-3">
                <a
                    href="data:image/svg+xml;base64,{{ base64_encode($svg) }}"
                    download="qr-{{ $table->code }}.svg"
                    class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700"
                >
                    Unduh QR (SVG)
                </a>

                <button
                    type="button"
                    onclick="navigator.clipboard.writeText(@js($url))"
                    class="rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-medium hover:bg-zinc-100"
                >
                    Salin URL
                </button>
            </div>

            <p class="mt-3 text-xs text-zinc-600">
                Jangan membagikan URL rahasia ini. Setelah QR dirotasi,
                segera ganti cetakan lama karena token sebelumnya sudah dicabut.
            </p>
        </section>
    @else
        <section class="rounded-xl border border-zinc-200 bg-white p-5">
            <h2 class="font-semibold">
                Tidak ada token baru untuk ditampilkan
            </h2>

            <p class="mt-2 text-sm text-zinc-500">
                Kembali ke daftar meja lalu pilih Terbitkan QR atau Rotate QR.
                Token mentah tidak ditampilkan kembali setelah flash session berakhir.
            </p>
        </section>
    @endif

    <div class="mt-6">
        <a
            href="{{ route('admin.tables.index') }}"
            class="text-sm font-medium underline"
        >
            &larr; Kembali ke daftar meja
        </a>
    </div>
@endcomponent
