{{-- One answer as a <dt>/<dd> pair, formatted by its type in SurveyResponse::FIELDS. --}}
@php
    $value = match ($field['type']) {
        'choice' => $response->choiceLabel($name),
        'choices' => $response->{$name} !== [] ? implode('、', $response->selectedChoiceLabels($name)) : null,
        'number' => $response->{$name} !== null ? $response->{$name}.' 分' : null,
        default => filled($response->{$name}) ? $response->{$name} : null,
    };
@endphp
<div class="{{ $field['type'] === 'choices' ? 'sm:col-span-2' : '' }}">
    <dt class="text-xs font-medium text-slate-500">{{ $field['label'] }}</dt>
    <dd class="mt-1 text-sm {{ $value === null ? 'text-slate-400' : 'text-slate-900' }} {{ $field['type'] === 'textarea' ? 'whitespace-pre-line leading-relaxed' : '' }}">{{ $value ?? '未回答' }}</dd>
</div>
