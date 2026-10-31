@props([
    'title' => 'Tidak ada data',
    'message' => 'Belum ada data yang tersedia.',
])

<div
    style="
        padding: 40px 20px;
        text-align: center;
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
    "
>
    <h3 style="margin-top: 0;">
        {{ $title }}
    </h3>

    <p style="color: #64748b;">
        {{ $message }}
    </p>
</div>