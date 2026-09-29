@php
    $fieldsBySection = App\Models\SurveyResponse::fieldsBySection();
    $sections = App\Models\SurveyResponse::SECTIONS;
@endphp

<form method="POST" action="{{ $action }}" class="space-y-5">
    @csrf
    @if ($response->exists)
        @method('PUT')
    @endif

    <x-section-card :title="$sections['basic']">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-field name="employee_id" label="請求書の作成担当者（回答者）" required
                hint="ご自身の氏名を選んでください。">
                <x-select name="employee_id" :selected="$response->employee_id" placeholder="氏名を入力して検索"
                    data-searchable data-employee-select>
                    @foreach ($employeesByOffice as $officeName => $employees)
                        <optgroup label="{{ $officeName }}">
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}" data-office-id="{{ $employee->office_id }}"
                                    @selected((int) old('employee_id', $response->employee_id) === $employee->id)>
                                    {{ $employee->name }}
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </x-select>
            </x-field>

            <x-field name="customer_id" label="顧客名" required
                hint="請求書を作成している顧客を選んでください。1社で請求書を複数に分けている場合は、請求書ごとに1件ずつ登録してください。">
                <x-select name="customer_id" :selected="$response->customer_id" placeholder="顧客名・顧客コードを入力して検索"
                    data-searchable>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}"
                            @selected((int) old('customer_id', $response->customer_id) === $customer->id)>
                            {{ $customer->code }}：{{ $customer->name }}
                        </option>
                    @endforeach
                </x-select>
            </x-field>

            <x-field name="office_id" label="営業所・拠点" required>
                <x-select name="office_id" :selected="$response->office_id" placeholder="選択してください" data-office-select
                    :data-office-picked="$response->exists || old('office_id') ? 'true' : null">
                    @foreach ($offices as $office)
                        <option value="{{ $office->id }}" @selected((int) old('office_id', $response->office_id) === $office->id)>
                            {{ $office->name }}
                        </option>
                    @endforeach
                </x-select>
            </x-field>
        </div>
    </x-section-card>

    @foreach ($sections as $sectionKey => $sectionLabel)
        @continue($sectionKey === 'basic')
        @php($fields = $fieldsBySection[$sectionKey] ?? [])
        @continue($fields === [])

        <x-section-card :title="$sectionLabel">
            <div class="grid gap-4 {{ $sectionKey === 'free_text' ? '' : 'sm:grid-cols-2' }}">
                @foreach ($fields as $name => $field)
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
                            <textarea name="{{ $name }}" id="{{ $name }}" rows="4"
                                class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm shadow-xs focus:border-slate-500 focus:ring-1 focus:ring-slate-500"
                            >{{ old($name, $response->{$name}) }}</textarea>
                        @endif
                    </x-field>
                @endforeach
            </div>
        </x-section-card>
    @endforeach

    <div class="flex flex-wrap items-center gap-3">
        <button type="submit"
            class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
            {{ $response->exists ? '更新する' : '登録する' }}
        </button>

        <a href="{{ route('responses.index') }}" class="text-sm text-slate-600 underline underline-offset-2 hover:text-slate-900">
            回答一覧に戻る
        </a>
    </div>
</form>

@pushOnce('scripts')
    <script>
        // Picking a respondent fills in their own office, until someone chooses the office
        // by hand: from then on (and on an answer that already has one) it is left alone.
        document.addEventListener('change', (event) => {
            const officeSelect = document.querySelector('[data-office-select]');

            if (! officeSelect) {
                return;
            }

            if (event.target === officeSelect) {
                officeSelect.dataset.officePicked = 'true';

                return;
            }

            const employeeSelect = event.target.closest('[data-employee-select]');
            const officeId = employeeSelect?.selectedOptions[0]?.dataset.officeId;

            if (officeId && officeSelect.dataset.officePicked !== 'true') {
                officeSelect.value = officeId;
            }
        });
    </script>
@endPushOnce
