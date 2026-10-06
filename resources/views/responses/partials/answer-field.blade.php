{{-- One answer's input, chosen by its type in SurveyResponse::FIELDS. --}}
@php
    // A follow-up (`asked_if`) shows only while an option asking it is chosen (CSS only, via
    // group-has on the options' data-asks-{field, hyphenated}). Tailwind needs each class written
    // out in full, and reads `_` as a space, so a new follow-up gets a hyphenated line here.
    $followUpClass = [
        'detail_unmailed_option_ids' => 'hidden group-has-[[data-asks-detail-unmailed-option-ids]:checked]/form:block',
    ][$name] ?? null;
@endphp
<x-field accent :name="$name" :label="$field['label']" :hint="$field['hint'] ?? null" :note="$field['note'] ?? null"
    :multiple="$field['type'] === 'choices'" :required="App\Models\SurveyResponse::isRequired($name)" :class="$followUpClass"
    :data-reveal="$followUpClass !== null">
    @if ($field['type'] === 'choices')
        {{-- After a rejected submit the boxes come back as they were sent, even when none was ticked. --}}
        @php
            $checkedIds = array_map(intval(...), old() ? old($name, []) : $response->{$name});
            $otherName = App\Models\SurveyResponse::otherInputName($name);
        @endphp
        {{-- The その他 text input shows only while その他 is ticked (CSS only, via group-has). --}}
        <div class="group/choices space-y-2">
            {{-- Tiles: the real checkbox stays (visually hidden) for the keyboard and the その他 rule;
                 the corner box and the lift show the ticked state. Long option lists get wider tiles. --}}
            @php
                $options = $catalog->optionsIncluding($field['category'], $response->{$name}, $field['except'] ?? []);
                $wide = $options->contains(fn ($option): bool => mb_strlen($option->label) > 8);
            @endphp
            <div role="group" aria-label="{{ $field['label'] }}"
                class="grid gap-2.5 {{ $wide ? 'grid-cols-[repeat(auto-fill,minmax(230px,1fr))]' : 'grid-cols-[repeat(auto-fill,minmax(140px,1fr))]' }}">
                @foreach ($options as $option)
                    <label class="group/tile relative flex min-h-12 cursor-pointer items-center rounded-xl border-[1.5px] border-slate-200 bg-white py-2.5 pr-10 pl-3.5 text-sm text-slate-800 transition select-none hover:border-orange-200 has-checked:-translate-y-px has-checked:border-orange-600 has-checked:bg-orange-50 has-checked:text-orange-900 has-checked:shadow-[0_6px_16px_rgba(234,88,12,0.18)] has-focus-visible:ring-2 has-focus-visible:ring-orange-500 has-focus-visible:ring-offset-2 motion-reduce:transition-none motion-reduce:has-checked:translate-y-0">
                        <input type="checkbox" name="{{ $name }}[]" value="{{ $option->id }}" autocomplete="off"
                            @checked(in_array($option->id, $checkedIds, true))
                            @if ($option->value === 'other') data-other @endif
                            class="sr-only">
                        {{ $option->label }}{{ $option->is_active ? '' : '（無効）' }}
                        <span aria-hidden="true"
                            class="absolute top-1/2 right-3 grid size-[18px] -translate-y-1/2 place-items-center rounded-md border-[1.5px] border-slate-300 bg-white transition group-has-checked/tile:border-orange-600 group-has-checked/tile:bg-orange-600 motion-reduce:transition-none">
                            {{-- The tick draws itself in when ticked (its stroke is revealed along its length). --}}
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="none" stroke="currentColor"
                                stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="size-3 text-white">
                                <path d="M4.5 10.5l3.5 3.5 7.5-8" pathLength="1"
                                    class="[stroke-dasharray:1] [stroke-dashoffset:1] transition-[stroke-dashoffset] delay-75 duration-300 ease-out group-has-checked/tile:[stroke-dashoffset:0]" />
                            </svg>
                        </span>
                    </label>
                @endforeach
            </div>
            <div class="hidden sm:max-w-md group-has-[[data-other]:checked]/choices:block">
                <x-text-input :name="$otherName" :value="$response->otherText($name)" maxlength="100"
                    placeholder="「その他」の内容をご記入ください" aria-label="{{ $field['label'] }}（その他の内容）" />
            </div>
        </div>
        @error($name.'.*')
            <p class="text-xs text-rose-600">{{ $message }}</p>
        @enderror
        @error($otherName)
            <p class="text-xs text-rose-600">{{ $message }}</p>
        @enderror
    @elseif ($field['type'] === 'choice')
        {{-- One-answer questions use the same tiles as the multi-selects, with a round mark. The
             empty hidden input is sent when nothing is chosen, so clearing an answer saves as
             未回答; a chosen radio comes later under the same name and wins. --}}
        @php
            $options = $catalog->optionsIncluding($field['category'], $response->{$name}, $field['except'] ?? []);
            $wide = $options->contains(fn ($option): bool => mb_strlen($option->label) > 8);
            $chosenId = (int) old($name, $response->{$name});
        @endphp
        <div class="group/choices space-y-2">
            <input type="hidden" name="{{ $name }}" value="">
            <div role="radiogroup" aria-label="{{ $field['label'] }}"
                class="grid gap-2.5 {{ $wide ? 'grid-cols-[repeat(auto-fill,minmax(230px,1fr))]' : 'grid-cols-[repeat(auto-fill,minmax(140px,1fr))]' }}">
                @foreach ($options as $option)
                    <label class="group/tile relative flex min-h-12 cursor-pointer items-center rounded-xl border-[1.5px] border-slate-200 bg-white py-2.5 pr-10 pl-3.5 text-sm text-slate-800 transition select-none hover:border-orange-200 has-checked:-translate-y-px has-checked:border-orange-600 has-checked:bg-orange-50 has-checked:text-orange-900 has-checked:shadow-[0_6px_16px_rgba(234,88,12,0.18)] has-focus-visible:ring-2 has-focus-visible:ring-orange-500 has-focus-visible:ring-offset-2 motion-reduce:transition-none motion-reduce:has-checked:translate-y-0">
                        <input type="radio" name="{{ $name }}" value="{{ $option->id }}" autocomplete="off"
                            @checked($chosenId === $option->id)
                            @if ($option->value === App\Models\SurveyResponse::COVER_ONLY) data-cover-only @endif
                            @foreach (App\Models\SurveyResponse::followUpsAskedBy($name, $option->value) as $followUp)
                                data-asks-{{ str_replace('_', '-', $followUp) }}
                            @endforeach
                            @if (($field['other'] ?? false) && $option->value === 'other') data-other @endif
                            class="sr-only">
                        {{ $option->label }}{{ $option->is_active ? '' : '（無効）' }}
                        <span aria-hidden="true"
                            class="absolute top-1/2 right-3 grid size-[18px] -translate-y-1/2 place-items-center rounded-full border-[1.5px] border-slate-300 bg-white transition group-has-checked/tile:border-orange-600 motion-reduce:transition-none">
                            <span class="size-2 scale-0 rounded-full bg-orange-600 transition group-has-checked/tile:scale-100 motion-reduce:transition-none"></span>
                        </span>
                    </label>
                @endforeach
            </div>
            @unless (App\Models\SurveyResponse::isRequired($name))
                <button type="button" data-choice-clear="{{ $name }}"
                    class="hidden items-center gap-1 text-xs text-slate-500 underline-offset-2 hover:text-slate-800 hover:underline group-has-checked/choices:inline-flex">
                    選択を外す
                </button>
            @endunless
            @if ($field['other'] ?? false)
                @php($otherName = App\Models\SurveyResponse::otherInputName($name))
                <div class="hidden sm:max-w-md group-has-[[data-other]:checked]/choices:block">
                    <x-text-input :name="$otherName" :value="$response->{$otherName}" maxlength="100"
                        placeholder="「その他」の内容をご記入ください" aria-label="{{ $field['label'] }}（その他の内容）" />
                </div>
                @error($otherName)
                    <p class="text-xs text-rose-600">{{ $message }}</p>
                @enderror
            @endif
        </div>
    @elseif ($field['type'] === 'number')
        <div class="flex items-center gap-2">
            <x-text-input :name="$name" type="number" :value="$response->{$name}" min="0" max="9999"
                placeholder="例：45" class="max-w-40" />
            <span class="text-sm text-slate-700">分</span>
        </div>
    @else
        <textarea name="{{ $name }}" id="{{ $name }}" rows="4" autocomplete="off"
            class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm shadow-xs focus:border-slate-500 focus:ring-1 focus:ring-slate-500"
        >{{ old($name, $response->{$name}) }}</textarea>
    @endif
</x-field>
