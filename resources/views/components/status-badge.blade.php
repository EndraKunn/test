@props([
    'status' => 'pending',
])

@php
    $styles = [
        'success' => 'background:#dcfce7; color:#166534;',
        'pending' => 'background:#fef3c7; color:#92400e;',
        'danger' => 'background:#fee2e2; color:#991b1b;',
    ];

    $style = $styles[$status] ?? $styles['pending'];
@endphp

<span
    style="
        {{ $style }}
        display: inline-block;
        padding: 6px 10px;
        border-radius: 999px;
        font-size: 14px;
        font-weight: 600;
    "
>
    {{ ucfirst($status) }}
</span>