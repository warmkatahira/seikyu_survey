@props(['active'])

@if ($active)
    <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">有効</span>
@else
    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-500">無効</span>
@endif
