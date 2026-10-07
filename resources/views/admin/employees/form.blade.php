<x-layout :title="$employee->exists ? '従業員の編集' : '従業員の追加'"
    :heading="$employee->exists ? '従業員の編集' : '従業員の追加'">

    <form method="POST" action="{{ $employee->exists ? route('admin.employees.update', $employee) : route('admin.employees.store') }}"
        class="max-w-xl space-y-5">
        @csrf
        @if ($employee->exists)
            @method('PUT')
        @endif

        <x-section-card>
            <div class="space-y-4">
                <x-field name="code" label="従業員コード" required hint="CSVで突き合わせるキーです。後から変更すると別の従業員として扱われます。">
                    <x-text-input name="code" :value="$employee->code" required />
                </x-field>

                <x-field name="name" label="氏名" required>
                    <x-text-input name="name" :value="$employee->name" required />
                </x-field>

                <x-field name="office_id" label="所属営業所">
                    <x-select name="office_id" :selected="$employee->office_id" placeholder="（未設定）">
                        @foreach ($offices as $office)
                            <option value="{{ $office->id }}" @selected((int) old('office_id', $employee->office_id) === $office->id)>
                                {{ $office->name }}
                            </option>
                        @endforeach
                    </x-select>
                </x-field>

                <x-field name="sort_order" label="表示順" hint="小さい数字ほど選択肢の上に表示されます。">
                    <x-text-input name="sort_order" type="number" :value="$employee->sort_order ?? 0" min="0" class="max-w-32" />
                </x-field>

                <x-checkbox name="is_active" label="有効（回答画面の選択肢に表示する）" :checked="$employee->is_active ?? true" />
            </div>
        </x-section-card>

        <div class="flex items-center gap-3">
            <button type="submit" class="rounded-md bg-teal-900 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">
                {{ $employee->exists ? '更新する' : '追加する' }}
            </button>
            <a href="{{ route('admin.employees.index') }}" class="text-sm text-slate-600 underline underline-offset-2">一覧に戻る</a>
        </div>
    </form>
</x-layout>
