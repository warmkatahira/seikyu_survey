<x-layout title="従業員マスタ" heading="従業員マスタ"
    subheading="回答画面の「請求書の作成担当者（回答者）」の選択肢になります。">

    <div class="mb-4 flex justify-end">
        <a href="{{ route('admin.employees.create') }}"
            class="rounded-md bg-teal-900 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">＋ 従業員を追加</a>
    </div>

    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs text-slate-600">
                <tr>
                    <th class="px-3 py-2 font-medium">従業員コード</th>
                    <th class="px-3 py-2 font-medium">氏名</th>
                    <th class="px-3 py-2 font-medium">所属営業所</th>
                    <th class="px-3 py-2 font-medium">回答数</th>
                    <th class="px-3 py-2 font-medium">表示順</th>
                    <th class="px-3 py-2 font-medium">状態</th>
                    <th class="px-3 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($employees as $employee)
                    <tr class="hover:bg-slate-50">
                        <td class="px-3 py-2 text-xs">{{ $employee->code }}</td>
                        <td class="px-3 py-2 font-medium">{{ $employee->name }}</td>
                        <td class="px-3 py-2">{{ $employee->office?->name ?? '—' }}</td>
                        <td class="px-3 py-2 text-slate-500">{{ $employee->survey_responses_count }}</td>
                        <td class="px-3 py-2 text-slate-500">{{ $employee->sort_order }}</td>
                        <td class="px-3 py-2"><x-status-badge :active="$employee->is_active" /></td>
                        <td class="px-3 py-2 text-right whitespace-nowrap">
                            <div class="inline-flex items-center gap-2">
                                <x-edit-button :href="route('admin.employees.edit', $employee)" />
                                <x-delete-button :action="route('admin.employees.destroy', $employee)" :confirm="'「'.$employee->name.'」を削除します。よろしいですか？'" />
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-3 py-10 text-center text-slate-500">従業員が登録されていません。CSVインポートから一括登録できます。</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $employees->links() }}</div>

    <div class="mt-6">
        <x-master-csv :master="$master" />
    </div>
</x-layout>
