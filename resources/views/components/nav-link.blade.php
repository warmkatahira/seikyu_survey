@props(['active' => false])

<a {{ $attributes->merge([
    'class' => $active
        ? 'font-semibold text-slate-900 underline decoration-teal-500 decoration-2 underline-offset-8'
        : 'text-slate-600 hover:text-slate-900',
]) }}>{{ $slot }}</a>
