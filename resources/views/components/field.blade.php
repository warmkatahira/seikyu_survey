@props(['name', 'label', 'hint' => null, 'required' => false])

<div {{ $attributes->merge(['class' => 'space-y-1']) }}>
    <label for="{{ $name }}" class="block text-sm font-medium text-slate-800">
        {{ $label }}
        @if ($required)
            <span class="ms-1 text-xs font-normal text-rose-600">必須</span>
        @endif
    </label>

    @if ($hint)
        <p class="text-xs leading-relaxed text-slate-500">{{ $hint }}</p>
    @endif

    {{ $slot }}

    @error($name)
        <p class="text-xs text-rose-600">{{ $message }}</p>
    @enderror
</div>
