@props(['name', 'selected' => null, 'placeholder' => '選択してください', 'options' => []])

<select
    name="{{ $name }}"
    id="{{ $name }}"
    {{ $attributes->merge([
        'class' => 'w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm shadow-xs focus:border-slate-500 focus:ring-1 focus:ring-slate-500',
    ]) }}
>
    @if ($placeholder !== false)
        <option value="">{{ $placeholder }}</option>
    @endif

    @if (isset($slot) && trim($slot) !== '')
        {{ $slot }}
    @else
        @foreach ($options as $value => $label)
            <option value="{{ $value }}" @selected((string) old($name, $selected) === (string) $value)>{{ $label }}</option>
        @endforeach
    @endif
</select>
