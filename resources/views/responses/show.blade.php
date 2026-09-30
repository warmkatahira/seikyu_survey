@php
    $sections = App\Models\SurveyResponse::SECTIONS;
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
                    '顧客名' => $response->customer?->name,
                    '作成区分' => $response->billing_category,
                    '営業所・拠点' => $response->office?->name,
                    '回答日時 ／ 更新日時' => $response->created_at?->format('Y/m/d H:i').' ／ '.$response->updated_at?->format('Y/m/d H:i'),
                ] as $label => $value)
                    <div>
                        <dt class="text-xs font-medium text-slate-500">{{ $label }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $value ?? '—' }}</dd>
                    </div>
                @endforeach
            </dl>
        </x-section-card>

        @foreach ($sections as $sectionKey => $sectionLabel)
            @continue($sectionKey === 'basic')
            @php
                $fields = $fieldsBySection[$sectionKey] ?? [];
            @endphp
            @continue($fields === [])

            <x-section-card :title="$sectionLabel">
                <dl class="grid gap-x-6 gap-y-4 {{ $sectionKey === 'free_text' ? '' : 'sm:grid-cols-2' }}">
                    @foreach ($fields as $name => $field)
                        @php
                            $value = match ($field['type']) {
                                'choice' => $catalog->label($response->{$name}),
                                'number' => $response->{$name} !== null ? $response->{$name}.' 分' : null,
                                default => filled($response->{$name}) ? $response->{$name} : null,
                            };
                        @endphp
                        <div>
                            <dt class="text-xs font-medium text-slate-500">{{ $field['label'] }}</dt>
                            <dd class="mt-1 text-sm {{ $value === null ? 'text-slate-400' : 'text-slate-900' }} {{ $field['type'] === 'textarea' ? 'whitespace-pre-line leading-relaxed' : '' }}">{{ $value ?? '未回答' }}</dd>
                        </div>
                    @endforeach
                </dl>
            </x-section-card>
        @endforeach
    </div>
</x-layout>
