@props(['name', 'label', 'hint' => null, 'required' => false])

<div {{ $attributes->merge(['class' => 'space-y-1']) }}>
    <label for="{{ $name }}" class="flex items-center gap-2 text-sm font-medium text-slate-800">
        {{ $label }}
        @if ($required)
            <span class="inline-flex items-center rounded-full bg-rose-50 px-2 py-0.5 text-[11px] leading-none font-medium text-rose-600 ring-1 ring-rose-200 ring-inset">必須</span>
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
