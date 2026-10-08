{{-- 回答数の推移: running total of answers per day, with a hover crosshair (script in responses.index). --}}
@php
    $width = 560;
    $height = 250;
    [$left, $right, $top, $bottom] = [36, 16, 30, 28];
    $plotWidth = $width - $left - $right;
    $plotHeight = $height - $top - $bottom;

    $lastTotal = $days === [] ? 0 : end($days)['total'];
    // A round step that gives at most five gridlines above zero.
    $step = collect([1, 2, 5, 10, 20, 25, 50, 100, 200, 250, 500, 1000])->first(fn ($s) => $s * 5 >= $lastTotal) ?? 1000;
    $yMax = max($step, (int) ceil($lastTotal / $step) * $step);

    $count = count($days);
    $slot = $count > 1 ? $plotWidth / ($count - 1) : $plotWidth;
    $x = fn (int $i): float => $count > 1 ? $left + $i * $slot : $left + $plotWidth / 2;
    $y = fn (int $value): float => $top + (1 - $value / $yMax) * $plotHeight;
    $labelEvery = (int) max(1, ceil($count / 8));

    $line = collect($days)->map(fn ($day, $i) => ($i ? 'L' : 'M').round($x($i), 1).','.round($y($day['total']), 1))->implode('');
@endphp

<x-section-card title="回答数の推移" description="回答日ごとの累計件数です。">
    @if ($days === [])
        <p class="py-10 text-center text-sm text-slate-500">まだ回答がありません。</p>
    @else
        <div class="relative" data-trend-chart>
            <svg viewBox="0 0 {{ $width }} {{ $height }}" class="w-full" role="img" aria-label="回答数の累計推移：{{ end($days)['date']->isoFormat('M/D') }}時点で{{ $lastTotal }}件">
                @for ($value = 0; $value <= $yMax; $value += $step)
                    <line x1="{{ $left }}" x2="{{ $width - $right }}" y1="{{ $y($value) }}" y2="{{ $y($value) }}" class="stroke-slate-200" stroke-width="1" />
                    <text x="{{ $left - 6 }}" y="{{ $y($value) + 4 }}" text-anchor="end" class="fill-slate-500 text-[11px]">{{ $value }}</text>
                @endfor

                @if ($count > 1)
                    <path d="{{ $line }}L{{ $x($count - 1) }},{{ $y(0) }}L{{ $x(0) }},{{ $y(0) }}Z" class="fill-teal-600/10" />
                    <path d="{{ $line }}" fill="none" class="stroke-teal-600" stroke-width="2" stroke-linejoin="round" />
                @endif

                <line data-trend-crosshair y1="{{ $top }}" y2="{{ $height - $bottom }}" class="stroke-slate-400" stroke-dasharray="3 3" opacity="0" />
                <circle data-trend-dot r="4.5" class="fill-teal-600 stroke-white" stroke-width="2" opacity="0" />

                <circle cx="{{ $x($count - 1) }}" cy="{{ $y($lastTotal) }}" r="4.5" class="fill-teal-600 stroke-white" stroke-width="2" />
                <text x="{{ $x($count - 1) - 8 }}" y="{{ $y($lastTotal) - 10 }}" text-anchor="end" class="fill-slate-900 text-xs font-semibold">{{ number_format($lastTotal) }}件</text>

                @foreach ($days as $i => $day)
                    @if ($i % $labelEvery === 0 || $i === $count - 1)
                        <text x="{{ $x($i) }}" y="{{ $height - 8 }}" text-anchor="middle" class="fill-slate-500 text-[11px]">{{ $day['date']->isoFormat('M/D') }}</text>
                    @endif
                    <rect x="{{ $x($i) - $slot / 2 }}" y="{{ $top }}" width="{{ $slot }}" height="{{ $plotHeight }}" fill="transparent"
                        data-x="{{ $x($i) }}" data-y="{{ $y($day['total']) }}"
                        data-tip="{{ $day['date']->isoFormat('M/D（ddd）') }}　累計 {{ number_format($day['total']) }}件（この日 +{{ number_format($day['answers']) }}件）" />
                @endforeach
            </svg>
            <div data-trend-tip class="pointer-events-none absolute z-10 hidden rounded-md bg-slate-900 px-2.5 py-1.5 text-xs whitespace-nowrap text-white"></div>
        </div>
    @endif
</x-section-card>
