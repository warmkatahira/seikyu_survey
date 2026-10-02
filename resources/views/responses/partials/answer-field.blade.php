{{-- One answer's input, chosen by its type in SurveyResponse::FIELDS. --}}
<x-field :name="$name" :label="$field['label']" :hint="$field['hint'] ?? null"
    class="{{ in_array($field['type'], ['textarea', 'choices'], true) ? 'sm:col-span-2' : '' }}">
    @if ($field['type'] === 'choices')
        {{-- After a rejected submit the boxes come back as they were sent, even when none was ticked. --}}
        @php($checkedIds = array_map(intval(...), old() ? old($name, []) : $response->{$name}))
        <div class="flex flex-wrap gap-2" role="group" aria-label="{{ $field['label'] }}">
            @foreach ($catalog->optionsIncluding($field['category'], $response->{$name}) as $option)
                <label class="inline-flex cursor-pointer items-center gap-2 rounded-full border border-slate-300 bg-white px-4 py-1.5 text-sm text-slate-800 transition select-none hover:border-slate-400 has-checked:border-emerald-500 has-checked:bg-emerald-50 has-checked:text-emerald-800">
                    <input type="checkbox" name="{{ $name }}[]" value="{{ $option->id }}" autocomplete="off"
                        @checked(in_array($option->id, $checkedIds, true))
                        class="size-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    {{ $option->label }}{{ $option->is_active ? '' : '（無効）' }}
                </label>
            @endforeach
        </div>
        @error($name.'.*')
            <p class="text-xs text-rose-600">{{ $message }}</p>
        @enderror
    @elseif ($field['type'] === 'choice')
        <x-select :name="$name" :selected="$response->{$name}" placeholder="（未回答）">
            @foreach ($catalog->optionsIncluding($field['category'], $response->{$name}) as $option)
                <option value="{{ $option->id }}"
                    @selected((int) old($name, $response->{$name}) === $option->id)>
                    {{ $option->label }}{{ $option->is_active ? '' : '（無効）' }}
                </option>
            @endforeach
        </x-select>
    @elseif ($field['type'] === 'number')
        <x-text-input :name="$name" type="number" :value="$response->{$name}" min="0" max="9999"
            placeholder="例：45" class="sm:max-w-40" />
    @else
        <textarea name="{{ $name }}" id="{{ $name }}" rows="4" autocomplete="off"
            class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm shadow-xs focus:border-slate-500 focus:ring-1 focus:ring-slate-500"
        >{{ old($name, $response->{$name}) }}</textarea>
    @endif
</x-field>
