{{-- 回答の進み具合: share of customers answered, then one bar per office (unanswered offices included). --}}
@php
    $percent = $progress['customers'] > 0 ? (int) floor($progress['answered_customers'] / $progress['customers'] * 100) : 0;
    $maxAnswers = max(1, $progress['offices']->max('survey_responses_count') ?? 0);
@endphp

<x-section-card title="回答の進み具合" description="回答のあった顧客の割合と、営業所ごとの回答件数です。">
    <div class="mb-4 space-y-1.5">
        <div class="flex items-baseline justify-between text-xs text-slate-600">
            <span>回答のあった顧客</span>
            <span class="tabular-nums">
                <span class="text-xl font-semibold text-slate-900">{{ number_format($progress['answered_customers']) }}</span>
                / {{ number_format($progress['customers']) }}社（{{ $percent }}%）
            </span>
        </div>
        <div class="h-2.5 overflow-hidden rounded-full bg-slate-100">
            <div class="h-full rounded-full bg-teal-600" style="width: {{ $percent }}%"></div>
        </div>
    </div>

    <div class="grid grid-cols-[max-content_minmax(0,1fr)_max-content] items-center gap-x-3 gap-y-1.5 text-xs tabular-nums">
        @foreach ($progress['offices'] as $office)
            <span class="text-slate-600">{{ $office->name }}</span>
            <div class="h-3.5" title="{{ $office->name }}：{{ number_format($office->survey_responses_count) }}件">
                @if ($office->survey_responses_count > 0)
                    <div class="h-full rounded-e bg-teal-600" style="width: {{ $office->survey_responses_count / $maxAnswers * 100 }}%"></div>
                @else
                    <div class="h-full w-px bg-slate-300"></div>
                @endif
            </div>
            <span class="text-right {{ $office->survey_responses_count > 0 ? 'text-slate-900' : 'text-slate-400' }}">{{ number_format($office->survey_responses_count) }}件</span>
        @endforeach
    </div>
</x-section-card>
