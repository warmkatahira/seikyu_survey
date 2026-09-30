{{-- One answer's input, chosen by its type in SurveyResponse::FIELDS. --}}
<x-field :name="$name" :label="$field['label']" :hint="$field['hint'] ?? null"
    class="{{ $field['type'] === 'textarea' ? 'sm:col-span-2' : '' }}">
    @if ($field['type'] === 'choice')
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
