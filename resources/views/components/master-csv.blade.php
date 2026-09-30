@props(['master'])

<x-section-card title="CSVインポート / エクスポート"
    :description="'見出し行： '.implode(' , ', $master->headings())">
    <div class="flex flex-wrap items-end gap-4">
        <a href="{{ route('admin.masters.export', $master) }}"
            class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm hover:bg-slate-50">
            CSVをエクスポート
        </a>

        <form method="POST" action="{{ route('admin.masters.import', $master) }}" enctype="multipart/form-data"
            class="flex flex-wrap items-end gap-3">
            @csrf
            <div class="space-y-1">
                <label for="file" class="block text-xs font-medium text-slate-600">CSVファイルを選択</label>
                <input type="file" name="file" id="file" autocomplete="off" accept=".csv,text/csv" required
                    class="block text-sm file:me-3 file:rounded-md file:border-0 file:bg-slate-900 file:px-3 file:py-2 file:text-sm file:text-white hover:file:bg-slate-700">
            </div>
            <button type="submit" class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm hover:bg-slate-50">
                インポート
            </button>
        </form>
    </div>

    <p class="mt-3 text-xs leading-relaxed text-slate-500">
        {{ $master->codeHeading() }}が一致する行は上書き、無い行は新規追加します。1行でもエラーがあると取り込みは行われません。<br>
        文字コードは UTF-8 / Shift_JIS のどちらでも取り込めます。エクスポートは Excel でそのまま開ける UTF-8（BOM付き）です。
    </p>
</x-section-card>
