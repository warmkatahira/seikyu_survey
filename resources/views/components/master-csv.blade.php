@props(['master'])

<x-section-card title="CSVインポート / エクスポート"
    :description="'見出し行： '.implode(' , ', $master->headings())">
    <div class="flex flex-wrap items-end gap-4">
        <a href="{{ route('admin.masters.export', $master) }}"
            class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm hover:bg-slate-50">
            CSVをエクスポート
        </a>

        <form method="POST" action="{{ route('admin.masters.import', $master) }}" enctype="multipart/form-data"
            class="flex flex-1 flex-wrap items-end gap-3" data-csv-drop>
            @csrf
            <label for="file" data-csv-zone
                class="group flex min-w-72 flex-1 flex-col items-center justify-center gap-1 rounded-lg border-2 border-dashed border-slate-300 bg-slate-50 px-4 py-5 text-center transition hover:border-slate-400 hover:bg-white data-dragging:border-orange-500 data-dragging:bg-orange-50 data-picked:border-solid data-picked:border-orange-500 data-picked:bg-orange-50/60">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"
                    class="size-7 text-slate-400 transition group-data-dragging:-translate-y-0.5 group-data-dragging:text-orange-600 group-data-picked:text-orange-600">
                    <path d="M9.25 13.25a.75.75 0 0 0 1.5 0V4.636l2.955 3.129a.75.75 0 0 0 1.09-1.03l-4.25-4.5a.75.75 0 0 0-1.09 0l-4.25 4.5a.75.75 0 1 0 1.09 1.03L9.25 4.636v8.614Z" />
                    <path d="M3.5 12.75a.75.75 0 0 0-1.5 0v2.5A2.75 2.75 0 0 0 4.75 18h10.5A2.75 2.75 0 0 0 18 15.25v-2.5a.75.75 0 0 0-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5Z" />
                </svg>
                <span class="text-sm font-medium text-slate-700" data-csv-name>CSVファイルをここにドラッグ＆ドロップ</span>
                <span class="text-xs text-slate-500" data-csv-note>またはクリックしてファイルを選択</span>
                <input type="file" name="file" id="file" autocomplete="off" accept=".csv,text/csv" required class="sr-only">
            </label>
            <button type="submit" disabled data-csv-submit
                class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700 disabled:bg-slate-300">
                インポート
            </button>
            <p class="w-full text-xs text-rose-600" data-csv-error hidden></p>
        </form>
    </div>

    <p class="mt-3 text-xs leading-relaxed text-slate-500">
        {{ $master->codeHeading() }}が一致する行は上書き、無い行は新規追加します。1行でもエラーがあると取り込みは行われません。<br>
        文字コードは UTF-8 / Shift_JIS のどちらでも取り込めます。エクスポートは Excel でそのまま開ける UTF-8（BOM付き）です。
    </p>
</x-section-card>

@pushOnce('scripts')
    <script>
        // CSV import: a file can be dropped on the zone as well as picked by clicking it. Dropping
        // only selects the file; the import itself still waits for the インポート button.
        document.querySelectorAll('[data-csv-drop]').forEach((form) => {
            const zone = form.querySelector('[data-csv-zone]');
            const input = form.querySelector('input[type=file]');
            const submit = form.querySelector('[data-csv-submit]');
            const name = form.querySelector('[data-csv-name]');
            const note = form.querySelector('[data-csv-note]');
            const error = form.querySelector('[data-csv-error]');
            const idle = { name: name.textContent, note: note.textContent };

            const show = () => {
                const file = input.files[0];

                zone.toggleAttribute('data-picked', Boolean(file));
                submit.disabled = ! file;
                name.textContent = file ? file.name : idle.name;
                const size = file && (file.size < 1024
                    ? `${file.size} バイト`
                    : `${(file.size / 1024).toLocaleString('ja-JP', { maximumFractionDigits: 1 })} KB`);
                note.textContent = file ? `${size} ・ クリックで選び直せます` : idle.note;
            };

            input.addEventListener('change', () => {
                error.hidden = true;
                show();
            });

            // Counting enter/leave keeps the highlight steady while the pointer crosses child elements.
            let depth = 0;

            zone.addEventListener('dragenter', (event) => {
                event.preventDefault();
                depth++;
                zone.toggleAttribute('data-dragging', true);
            });

            zone.addEventListener('dragover', (event) => {
                event.preventDefault();
                event.dataTransfer.dropEffect = 'copy';
            });

            zone.addEventListener('dragleave', () => {
                if (--depth === 0) {
                    zone.removeAttribute('data-dragging');
                }
            });

            zone.addEventListener('drop', (event) => {
                event.preventDefault();
                depth = 0;
                zone.removeAttribute('data-dragging');

                const file = event.dataTransfer.files[0];

                if (! file) {
                    return;
                }

                if (! /\.csv$/i.test(file.name)) {
                    error.textContent = `「${file.name}」はCSVファイルではありません。拡張子が .csv のファイルをドロップしてください。`;
                    error.hidden = false;

                    return;
                }

                const files = new DataTransfer();
                files.items.add(file);
                input.files = files.files;
                error.hidden = true;
                show();
            });
        });

        // A file dropped just outside the zone would otherwise be opened by the browser,
        // leaving the page.
        if (document.querySelector('[data-csv-drop]')) {
            ['dragover', 'drop'].forEach((type) => window.addEventListener(type, (event) => {
                if (! event.target.closest?.('[data-csv-zone]')) {
                    event.preventDefault();
                }
            }));
        }
    </script>
@endPushOnce
