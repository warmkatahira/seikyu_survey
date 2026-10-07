<x-layout title="選択肢マスタ" heading="選択肢マスタ"
    subheading="回答画面のプルダウンの中身です。表示名の変更・並べ替え・選択肢の追加ができます。">

    <div class="space-y-5">
        @foreach ($categories as $category)
            <x-section-card>
                <x-slot:title>{{ $category->name }}</x-slot:title>

                <div class="mb-3 space-y-1 text-xs text-slate-500">
                    @if ($category->description)
                        <p>{{ $category->description }}</p>
                    @endif
                    <p>使用項目：{{ implode(' / ', $usedBy[$category->key] ?? ['（未使用）']) }}</p>
                </div>

                <div class="divide-y divide-slate-100 border-y border-slate-100">
                    @foreach ($category->options as $option)
                        <div class="flex flex-wrap items-center gap-3 py-2">
                            <form method="POST" action="{{ route('admin.choices.options.update', $option) }}"
                                class="flex flex-1 flex-wrap items-center gap-3">
                                @csrf
                                @method('PUT')

                                <input type="text" name="label" autocomplete="off" value="{{ $option->label }}" required
                                    class="min-w-60 flex-1 rounded-md border border-slate-300 px-3 py-1.5 text-sm">

                                <label class="flex items-center gap-1.5 text-xs text-slate-600">
                                    表示順
                                    <input type="number" name="sort_order" autocomplete="off" value="{{ $option->sort_order }}" min="0"
                                        class="w-20 rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                                </label>

                                <label class="flex items-center gap-1.5 text-xs text-slate-600">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" autocomplete="off" value="1" @checked($option->is_active)
                                        class="size-4 rounded border-slate-300 text-slate-900">
                                    有効
                                </label>

                                <button type="submit"
                                    class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs hover:bg-slate-50">
                                    保存
                                </button>
                            </form>

                            <x-delete-button :action="route('admin.choices.options.destroy', $option)"
                                :confirm="'「'.$option->label.'」を削除します。よろしいですか？'" />
                        </div>
                    @endforeach
                </div>

                <form method="POST" action="{{ route('admin.choices.options.store', $category) }}"
                    class="mt-3 flex flex-wrap items-center gap-3">
                    @csrf
                    <input type="text" name="label" autocomplete="off" placeholder="選択肢を追加" required
                        class="min-w-60 flex-1 rounded-md border border-slate-300 px-3 py-1.5 text-sm">
                    <label class="flex items-center gap-1.5 text-xs text-slate-600">
                        表示順
                        <input type="number" name="sort_order" autocomplete="off" value="{{ ($category->options->max('sort_order') ?? 0) + 10 }}"
                            min="0" class="w-20 rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                    </label>
                    <input type="hidden" name="is_active" value="1">
                    <button type="submit"
                        class="rounded-md bg-teal-900 px-3 py-1.5 text-xs font-medium text-white hover:bg-teal-800">
                        ＋ 追加
                    </button>
                </form>
            </x-section-card>
        @endforeach
    </div>
</x-layout>
