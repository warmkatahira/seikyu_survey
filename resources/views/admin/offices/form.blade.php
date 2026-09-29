<x-layout :title="$office->exists ? '営業所の編集' : '営業所の追加'"
    :heading="$office->exists ? '営業所の編集' : '営業所の追加'">

    <form method="POST" action="{{ $office->exists ? route('admin.offices.update', $office) : route('admin.offices.store') }}"
        class="max-w-xl space-y-5">
        @csrf
        @if ($office->exists)
            @method('PUT')
        @endif

        <x-section-card>
            <div class="space-y-4">
                <x-field name="code" label="営業所コード" required hint="CSVで突き合わせるキーです。後から変更すると別の営業所として扱われます。">
                    <x-text-input name="code" :value="$office->code" required />
                </x-field>

                <x-field name="name" label="営業所名" required>
                    <x-text-input name="name" :value="$office->name" required />
                </x-field>

                <x-field name="sort_order" label="表示順" hint="小さい数字ほど選択肢の上に表示されます。">
                    <x-text-input name="sort_order" type="number" :value="$office->sort_order ?? 0" min="0" class="max-w-32" />
                </x-field>

                <x-checkbox name="is_active" label="有効（回答画面の選択肢に表示する）" :checked="$office->is_active ?? true" />
            </div>
        </x-section-card>

        <div class="flex items-center gap-3">
            <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
                {{ $office->exists ? '更新する' : '追加する' }}
            </button>
            <a href="{{ route('admin.offices.index') }}" class="text-sm text-slate-600 underline underline-offset-2">一覧に戻る</a>
        </div>
    </form>
</x-layout>
