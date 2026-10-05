@php
    $sections = App\Models\SurveyResponse::SECTIONS;
    $groups = App\Models\SurveyResponse::GROUPS;
    $fieldsBySection = App\Models\SurveyResponse::fieldsBySection();
@endphp

<x-layout title="回答の閲覧" :heading="$response->customerLabel() ?: '回答の閲覧'">

    <div class="mb-4 flex flex-wrap items-center gap-3">
        <a href="{{ $backUrl }}" class="text-sm text-slate-600 underline underline-offset-2 hover:text-slate-900">← 回答一覧に戻る</a>

        <div class="ms-auto inline-flex items-center gap-2">
            <x-edit-button :href="route('responses.edit', $response)" />
            <x-delete-button :action="route('responses.destroy', $response)" confirm="この回答を削除します。よろしいですか？" />
        </div>
    </div>

    <div class="space-y-5">
        <x-section-card :title="$sections['basic']">
            <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2">
                @foreach ([
                    '請求書の作成担当者（回答者）' => $response->employee?->name,
                    '営業所・拠点' => $response->office?->name,
                    '顧客名' => $response->customer?->name,
                    '作成区分' => $response->billing_category,
                ] as $label => $value)
                    <div>
                        <dt class="text-xs font-medium text-slate-500">{{ $label }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $value ?? '—' }}</dd>
                    </div>
                @endforeach

                @foreach ($fieldsBySection['basic'][''] ?? [] as $name => $field)
                    @include('responses.partials.answer-value')
                @endforeach

                <div>
                    <dt class="text-xs font-medium text-slate-500">回答日時 ／ 更新日時</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $response->created_at?->format('Y/m/d H:i') }} ／ {{ $response->updated_at?->format('Y/m/d H:i') }}</dd>
                </div>
            </dl>
        </x-section-card>

        @foreach ($sections as $sectionKey => $sectionLabel)
            @continue($sectionKey === 'basic' || empty($fieldsBySection[$sectionKey]))
            {{-- 鑑のみ has no 明細 to show. --}}
            @continue($sectionKey === 'detail' && $response->isCoverOnly())

            <x-section-card :title="$sectionLabel" class="space-y-6">
                @foreach ($fieldsBySection[$sectionKey] as $groupKey => $fields)
                    <div>
                        @if ($groupKey !== '')
                            <h3 class="mb-3 border-l-4 border-emerald-500 pl-2 text-sm font-semibold text-slate-700">{{ $groups[$groupKey] }}</h3>
                        @endif

                        <dl class="grid gap-x-6 gap-y-4 {{ $sectionKey === 'free_text' ? '' : 'sm:grid-cols-2' }}">
                            @foreach ($fields as $name => $field)
                                @include('responses.partials.answer-value')
                            @endforeach
                        </dl>
                    </div>
                @endforeach
            </x-section-card>
        @endforeach
    </div>
</x-layout>
