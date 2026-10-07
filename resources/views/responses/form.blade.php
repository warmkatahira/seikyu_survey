@php
    $fieldsBySection = App\Models\SurveyResponse::fieldsBySection();
    $sections = App\Models\SurveyResponse::SECTIONS;
@endphp

<div class="mb-4">
    <a href="{{ route('responses.index') }}" class="text-sm text-slate-600 underline underline-offset-2 hover:text-slate-900">← 回答一覧に戻る</a>
</div>

{{-- group/form lets 明細について hide itself while 請求書の構成 is 鑑のみ (CSS only, via group-has). --}}
<form method="POST" action="{{ $action }}" class="group/form space-y-5" data-send-form>
    @csrf
    @if ($response->exists)
        @method('PUT')
    @endif

    {{-- Progress on the required questions, pinned to the top while scrolling: 「必須項目 12 / 16 回答済み」
         and a bar. 「あと◯件」 jumps to the first unanswered one. Hidden questions (明細について under
         鑑のみ) are left out of both counts. Filled in by the script below. --}}
    <div data-required-progress aria-live="polite"
        class="sticky top-[env(safe-area-inset-top,0px)] z-30 rounded-lg border border-slate-200 bg-white/95 px-4 py-2.5 shadow-sm backdrop-blur">
        <div class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-1 text-sm">
            <span class="text-slate-700">
                必須項目 <strong class="text-base text-slate-900 tabular-nums" data-required-answered></strong>
                / <span class="tabular-nums" data-required-total></span> 回答済み
            </span>
            <button type="button" data-required-jump
                class="text-xs font-medium underline-offset-2 not-data-done:text-rose-700 not-data-done:hover:underline data-done:cursor-default data-done:text-emerald-700"></button>
        </div>
        <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-slate-200">
            <div data-required-fill
                class="relative h-full w-0 overflow-hidden rounded-full bg-linear-to-r from-teal-600 to-teal-400 transition-[width] duration-300 motion-reduce:transition-none"></div>
        </div>
    </div>

    {{-- One question per row, so a question with a hint box never pushes its neighbour's input out of line. --}}
    <x-section-card :title="$sections['basic']">
        <div class="grid gap-5">
            <x-field accent name="employee_id" label="請求書の作成担当者（回答者）" required>
                <div class="sm:max-w-md">
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
                </div>
            </x-field>

            <x-field accent name="office_id" label="営業所・拠点" required>
                <x-select name="office_id" :selected="$response->office_id" placeholder="選択してください" data-office-select class="sm:max-w-md"
                    :data-office-picked="$response->exists || old('office_id') ? 'true' : null">
                    @foreach ($offices as $office)
                        <option value="{{ $office->id }}" @selected((int) old('office_id', $response->office_id) === $office->id)>
                            {{ $office->name }}
                        </option>
                    @endforeach
                </x-select>
            </x-field>

            <x-field accent name="customer_id" label="顧客名" required
                hint="顧客名の後ろの（）は、その顧客についてすでに登録されている回答の件数です。"
                note="1社で請求書を複数に分けている場合は、請求書ごとに1件ずつ登録し、「作成区分」で区別してください。">
                <div class="sm:max-w-md">
                    <x-select name="customer_id" :selected="$response->customer_id" placeholder="顧客名を入力して検索"
                        data-searchable>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}"
                                @selected((int) old('customer_id', $response->customer_id) === $customer->id)>
                                {{ $customer->name }}{{ $customer->survey_responses_count > 0 ? "（回答 {$customer->survey_responses_count}件）" : '（未回答）' }}
                            </option>
                        @endforeach
                    </x-select>
                </div>
            </x-field>

            <x-field accent name="billing_category" label="作成区分"
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
        @continue($sectionKey === 'basic' || empty($fieldsBySection[$sectionKey]))

        <div class="{{ $sectionKey === 'detail' ? 'group-has-[[data-cover-only]:checked]/form:hidden' : '' }}" @if ($sectionKey === 'detail') data-reveal @endif>
            <x-section-card :title="$sectionLabel">
                <div class="grid gap-5">
                    @foreach ($fieldsBySection[$sectionKey] as $name => $field)
                        @include('responses.partials.answer-field')
                    @endforeach
                </div>
            </x-section-card>
        </div>
    @endforeach

    <div class="flex flex-wrap items-center gap-3">
        {{-- Styled as "send": a filled accent pill with a paper-plane, distinct from the grey utility buttons. --}}
        <button type="submit" data-send-button
            class="send-button group inline-flex items-center gap-2 rounded-full bg-teal-600 px-7 py-2.5 text-sm font-semibold tracking-wide text-white shadow-md shadow-teal-600/25 transition hover:bg-teal-700 hover:shadow-lg focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-600 active:translate-y-px">
            <span data-send-label>{{ $response->exists ? '更新する' : '回答する' }}</span>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="send-plane size-4 transition group-hover:translate-x-0.5" aria-hidden="true">
                <path d="M3.105 2.288a.75.75 0 0 0-.826.95l1.414 4.926A1.5 1.5 0 0 0 5.135 9.25h6.115a.75.75 0 0 1 0 1.5H5.135a1.5 1.5 0 0 0-1.442 1.086l-1.414 4.926a.75.75 0 0 0 .826.95 28.897 28.897 0 0 0 15.293-7.155.75.75 0 0 0 0-1.114A28.897 28.897 0 0 0 3.105 2.288Z" />
            </svg>
        </button>
    </div>
</form>

@pushOnce('scripts')
    <script>
        // Restarts a one-off motion class from app.css on an element, so it plays again on every trigger.
        const replayMotion = (element, className) => {
            element.classList.remove(className);
            void element.offsetWidth;
            element.classList.add(className);
            element.addEventListener('animationend', () => element.classList.remove(className), { once: true });
        };

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

        // Required-question progress: a question counts as answered once a tile is chosen, or its
        // dropdown / text / number has a value. Questions hidden at the moment are skipped.
        (() => {
            const progress = document.querySelector('[data-required-progress]');

            if (! progress) {
                return;
            }

            const jump = progress.querySelector('[data-required-jump]');

            const isAnswered = (field) => {
                const picks = field.querySelectorAll('input[type=radio], input[type=checkbox]');

                if (picks.length > 0) {
                    return Array.from(picks).some((pick) => pick.checked);
                }

                const control = field.querySelector('select, input[type=number], input[type=text], textarea');

                return control !== null && control.value.trim() !== '';
            };

            const visibleFields = () => Array.from(document.querySelectorAll('[data-required-field]'))
                .filter((field) => field.offsetParent !== null);

            const fill = progress.querySelector('[data-required-fill]');
            let leftBefore = null;

            const refresh = () => {
                const fields = visibleFields();
                const answered = fields.filter(isAnswered).length;
                const left = fields.length - answered;

                progress.querySelector('[data-required-answered]').textContent = answered;
                progress.querySelector('[data-required-total]').textContent = fields.length;
                fill.style.width = `${fields.length === 0 ? 100 : (answered / fields.length) * 100}%`;

                // The last one just answered (not a page opening complete): the bar gleams and
                // 「すべて回答済み ✓」 pops in.
                if (leftBefore !== null && leftBefore > 0 && left === 0) {
                    replayMotion(fill, 'motion-shine');
                    replayMotion(jump, 'motion-pop');
                }
                leftBefore = left;

                jump.toggleAttribute('data-done', left === 0);
                jump.textContent = left === 0 ? 'すべて回答済み ✓' : `あと${left}件 →`;
                jump.title = left === 0 ? '' : '未回答の必須項目へ移動します';
            };

            jump.addEventListener('click', () => {
                const field = visibleFields().find((candidate) => ! isAnswered(candidate));

                if (field) {
                    field.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    field.querySelector('input:not([type=hidden]), select, textarea')?.focus({ preventScroll: true });
                    replayMotion(field, 'motion-flash');
                }
            });

            // After other handlers have run (the office filled in from the respondent, 選択を外す).
            ['input', 'change', 'click'].forEach((type) => document.addEventListener(type, () => requestAnimationFrame(refresh)));
            refresh();
        })();

        // A tile just chosen bounces, and a question or section that has just appeared (a
        // follow-up, 明細について when 鑑のみ is undone) slides in. Only on an actual change, so
        // nothing moves while the page opens.
        (() => {
            const revealables = Array.from(document.querySelectorAll('[data-send-form] [data-reveal]'));
            const shown = new Map(revealables.map((element) => [element, element.offsetParent !== null]));

            document.addEventListener('change', (event) => {
                const pick = event.target.closest('[data-send-form] input[type=radio], [data-send-form] input[type=checkbox]');

                if (pick?.checked) {
                    replayMotion(pick.closest('label'), 'motion-pop');
                }

                requestAnimationFrame(() => revealables.forEach((element) => {
                    const visible = element.offsetParent !== null;

                    if (visible && ! shown.get(element)) {
                        replayMotion(element, 'motion-reveal');
                    }
                    shown.set(element, visible);
                }));
            });
        })();

        // A question sent back with an error is brought into view (it shakes there, see app.css).
        document.querySelector('[data-send-form] [data-field-error]')?.scrollIntoView({ block: 'center' });

        // 選択を外す: a one-answer question goes back to 未回答 (the hidden empty input is then sent).
        document.addEventListener('click', (event) => {
            const button = event.target.closest('[data-choice-clear]');

            if (! button) {
                return;
            }

            button.closest('form').querySelectorAll(`input[type=radio][name="${button.dataset.choiceClear}"]`)
                .forEach((radio) => { radio.checked = false; });
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
