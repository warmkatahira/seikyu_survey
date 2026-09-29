@props(['name', 'checked' => false, 'label'])

<label class="flex items-center gap-2 text-sm text-slate-800">
    <input type="hidden" name="{{ $name }}" value="0">
    <input
        type="checkbox"
        name="{{ $name }}"
        id="{{ $name }}"
        value="1"
        @checked((bool) old($name, $checked))
        class="size-4 rounded border-slate-300 text-slate-900 focus:ring-slate-500"
    >
    {{ $label }}
</label>
