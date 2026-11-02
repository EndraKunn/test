
@props([
    'type' => 'text',
    'name' => '',
    'label' => null,
    'placeholder' => '',
    'id' => null,
])

@php
    $inputId = $id ?: ($name ?: 'input-field');
@endphp

<div class="w-full">
    @if ($label)
        <label
            for="{{ $inputId }}"
            class="mb-1.5 block text-sm font-semibold text-slate-700"
        >
            {{ $label }}
        </label>
    @endif

    <input
        id="{{ $inputId }}"
        type="{{ $type }}"
        name="{{ $name }}"
        placeholder="{{ $placeholder }}"
        {{ $attributes->merge([
            'class' => 'block w-full min-h-11 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20'
        ]) }}
    >
</div>
