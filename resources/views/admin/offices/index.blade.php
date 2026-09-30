<x-layout title="営業所マスタ" heading="営業所マスタ"
    subheading="回答画面の「営業所・拠点」の選択肢になります。">

    <div class="mb-4 flex justify-end">
        <a href="{{ route('admin.offices.create') }}"
            class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">＋ 営業所を追加</a>
    </div>

    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs text-slate-600">
                <tr>
                    <th class="px-3 py-2 font-medium">営業所コード</th>
                    <th class="px-3 py-2 font-medium">営業所名</th>
                    <th class="px-3 py-2 font-medium">従業員</th>
                    <th class="px-3 py-2 font-medium">表示順</th>
                    <th class="px-3 py-2 font-medium">状態</th>
                    <th class="px-3 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($offices as $office)
                    <tr class="hover:bg-slate-50">
                        <td class="px-3 py-2 font-mono text-xs">{{ $office->code }}</td>
                        <td class="px-3 py-2 font-medium">{{ $office->name }}</td>
                        <td class="px-3 py-2 text-slate-500">{{ $office->employees_count }}</td>
                        <td class="px-3 py-2 text-slate-500">{{ $office->sort_order }}</td>
                        <td class="px-3 py-2">
                            <x-status-badge :active="$office->is_active" />
                        </td>
                        <td class="px-3 py-2 text-right whitespace-nowrap">
                            <div class="inline-flex items-center gap-2">
                                <x-edit-button :href="route('admin.offices.edit', $office)" />
                                <x-delete-button :action="route('admin.offices.destroy', $office)" :confirm="'「'.$office->name.'」を削除します。よろしいですか？'" />
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-3 py-10 text-center text-slate-500">営業所が登録されていません。</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $offices->links() }}</div>

    <div class="mt-6">
        <x-master-csv :master="$master" />
    </div>
</x-layout>
