@props(['title' => null, 'description' => null])

<section class="rounded-lg border border-slate-200 bg-white shadow-xs">
    @if ($title)
        <header class="border-b border-slate-200 px-4 py-3">
            <h2 class="text-sm font-semibold text-slate-900">{{ $title }}</h2>
            @if ($description)
                <p class="mt-1 text-xs text-slate-500">{{ $description }}</p>
            @endif
        </header>
    @endif

    <div {{ $attributes->merge(['class' => 'p-4']) }}>
        {{ $slot }}
    </div>
</section>
