@props([
    'type' => 'text',
    'name' => '',
    'placeholder' => '',
])

<input
    type="{{ $type }}"
    name="{{ $name }}"
    placeholder="{{ $placeholder }}"
    {{ $attributes->merge([
        'style' => '
            width: 100%;
            min-height: 44px;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: white;
        '
    ]) }}
>