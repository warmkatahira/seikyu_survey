@php
    $fieldsBySection = App\Models\SurveyResponse::fieldsBySection();
    $sections = App\Models\SurveyResponse::SECTIONS;
@endphp

<div class="mb-4">
    <a href="{{ route('responses.index') }}" class="text-sm text-slate-600 underline underline-offset-2 hover:text-slate-900">← 回答一覧に戻る</a>
</div>

<form method="POST" action="{{ $action }}" class="space-y-5" data-send-form>
    @csrf
    @if ($response->exists)
        @method('PUT')
    @endif

    <x-section-card :title="$sections['basic']">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-field name="employee_id" label="請求書の作成担当者（回答者）" required>
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

            <x-field name="customer_id" label="顧客名" required
                hint="請求書を作成している顧客を選んでください。1社で請求書を複数に分けている場合は、請求書ごとに1件ずつ登録し、「作成区分」で区別してください。">
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

            <x-field name="billing_category" label="作成区分"
                hint="1社で請求書を分けて作成している場合のみ、どの請求書かがわかる名前をご記入ください（例：卸、通販）。1社1枚の場合は空欄で構いません。">
                <x-text-input name="billing_category" :value="$response->billing_category" maxlength="50"
                    placeholder="例：通販" class="sm:max-w-60" />
            </x-field>

            @foreach ($fieldsBySection['basic'] ?? [] as $name => $field)
                @include('responses.partials.answer-field')
            @endforeach
        </div>
    </x-section-card>

    @foreach ($sections as $sectionKey => $sectionLabel)
        @continue($sectionKey === 'basic')
        @php($fields = $fieldsBySection[$sectionKey] ?? [])
        @continue($fields === [])

        <x-section-card :title="$sectionLabel">
            <div class="grid gap-4 {{ $sectionKey === 'free_text' ? '' : 'sm:grid-cols-2' }}">
                @foreach ($fields as $name => $field)
                    @include('responses.partials.answer-field')
                @endforeach
            </div>
        </x-section-card>
    @endforeach

    <div class="flex flex-wrap items-center gap-3">
        {{-- Styled as "send": a filled accent pill with a paper-plane, distinct from the grey utility buttons. --}}
        <button type="submit" data-send-button
            class="send-button group inline-flex items-center gap-2 rounded-full bg-emerald-600 px-7 py-2.5 text-sm font-semibold tracking-wide text-white shadow-md shadow-emerald-600/25 transition hover:bg-emerald-700 hover:shadow-lg focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600 active:translate-y-px">
            <span data-send-label>{{ $response->exists ? '更新する' : '回答する' }}</span>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="send-plane size-4 transition group-hover:translate-x-0.5" aria-hidden="true">
                <path d="M3.105 2.288a.75.75 0 0 0-.826.95l1.414 4.926A1.5 1.5 0 0 0 5.135 9.25h6.115a.75.75 0 0 1 0 1.5H5.135a1.5 1.5 0 0 0-1.442 1.086l-1.414 4.926a.75.75 0 0 0 .826.95 28.897 28.897 0 0 0 15.293-7.155.75.75 0 0 0 0-1.114A28.897 28.897 0 0 0 3.105 2.288Z" />
            </svg>
        </button>
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

        // Sending: a veil stops anything else being clicked and the button's paper plane takes
        // off. The answer goes in the background so the flight shows for at least
        // SEND_MIN_MS, or for as long as saving takes if that is longer. If the server
        // rejects the answer (validation) or cannot be reached, the form is sent the ordinary
        // way instead, so the page comes back with its error messages as before.
        const SEND_MIN_MS = 2000;

        document.addEventListener('submit', async (event) => {
            const form = event.target.closest('[data-send-form]');
            const button = form?.querySelector('[data-send-button]');

            if (! button) {
                return;
            }

            event.preventDefault();

            if (button.dataset.sending === 'true') {
                return;
            }

            const veil = document.createElement('div');
            veil.className = 'send-veil';
            veil.dataset.sendVeil = '';
            veil.innerHTML = `
                <div class="send-card" role="status">
                    <div class="send-sky" aria-hidden="true">
                        <span class="send-cloud"></span><span class="send-cloud"></span><span class="send-cloud"></span>
                        <span class="send-wind"></span><span class="send-wind"></span><span class="send-wind"></span>
                        <svg class="send-flyer" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M3.105 2.288a.75.75 0 0 0-.826.95l1.414 4.926A1.5 1.5 0 0 0 5.135 9.25h6.115a.75.75 0 0 1 0 1.5H5.135a1.5 1.5 0 0 0-1.442 1.086l-1.414 4.926a.75.75 0 0 0 .826.95 28.897 28.897 0 0 0 15.293-7.155.75.75 0 0 0 0-1.114A28.897 28.897 0 0 0 3.105 2.288Z" />
                        </svg>
                    </div>
                    <p class="send-message">回答を送信しています</p>
                </div>`;
            document.body.append(veil);

            const label = button.querySelector('[data-send-label]');
            button.dataset.sending = 'true';
            button.dataset.idleLabel = label.textContent;
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            label.textContent = '送信中…';

            const minimum = new Promise((resolve) => setTimeout(resolve, SEND_MIN_MS));
            const saving = fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            })
                .then(async (response) => (response.ok ? (await response.json()).redirect : null))
                .catch(() => null);

            const [redirect] = await Promise.all([saving, minimum]);

            if (redirect) {
                window.location.assign(redirect);
            } else {
                form.submit();
            }
        });

        // Coming back with the browser's Back button can restore the page mid-send; reset it.
        window.addEventListener('pageshow', (event) => {
            if (! event.persisted) {
                return;
            }

            document.querySelectorAll('[data-send-veil]').forEach((veil) => veil.remove());
            document.querySelectorAll('[data-send-button][data-sending]').forEach((button) => {
                button.disabled = false;
                button.removeAttribute('aria-busy');
                button.querySelector('[data-send-label]').textContent = button.dataset.idleLabel;
                delete button.dataset.sending;
            });
        });
    </script>
@endPushOnce
