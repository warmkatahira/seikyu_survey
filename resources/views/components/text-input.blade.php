@props(['name', 'value' => null, 'type' => 'text'])

<input
    type="{{ $type }}"
    name="{{ $name }}"
    id="{{ $name }}"
    autocomplete="off"
    value="{{ old($name, $value) }}"
    {{ $attributes->merge([
        'class' => 'w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm shadow-xs focus:border-slate-500 focus:ring-1 focus:ring-slate-500',
    ]) }}
>
