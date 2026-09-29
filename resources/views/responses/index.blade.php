<x-layout title="回答一覧" heading="回答一覧"
    subheading="登録済みの回答です。内容の修正が必要な場合は「編集」から直してください。">

    <div class="mb-5 grid gap-3 sm:grid-cols-3">
        <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
            <p class="text-xs text-slate-500">記入済みの顧客件数</p>
            <p class="mt-1 text-2xl font-semibold">{{ number_format($totals['answered']) }}<span class="ms-1 text-sm font-normal text-slate-500">件</span></p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
            <p class="text-xs text-slate-500">作成時間の合計</p>
            <p class="mt-1 text-2xl font-semibold">{{ number_format($totals['minutes']) }}<span class="ms-1 text-sm font-normal text-slate-500">分</span></p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
            <p class="text-xs text-slate-500">うち「自分しか作れない」</p>
            <p class="mt-1 text-2xl font-semibold">{{ number_format($totals['sole_owner']) }}<span class="ms-1 text-sm font-normal text-slate-500">件</span></p>
        </div>
    </div>

    <div class="mb-4 flex flex-wrap items-end gap-3">
        <form method="GET" action="{{ route('responses.index') }}" class="flex flex-wrap items-end gap-3">
            <div class="space-y-1">
                <label for="employee_id" class="block text-xs font-medium text-slate-600">回答者で絞り込む</label>
                <select name="employee_id" id="employee_id" data-searchable
                    class="w-56 rounded-md border border-slate-300 bg-white px-3 py-2 text-sm shadow-xs">
                    <option value="">すべて</option>
                    @foreach ($employeesByOffice as $officeName => $employees)
                        <optgroup label="{{ $officeName }}">
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}" @selected($employeeId === $employee->id)>{{ $employee->name }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>

            <div class="space-y-1">
                <label for="customer_id" class="block text-xs font-medium text-slate-600">顧客で絞り込む</label>
                <select name="customer_id" id="customer_id" data-searchable
                    class="w-72 rounded-md border border-slate-300 bg-white px-3 py-2 text-sm shadow-xs">
                    <option value="">すべて</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}" @selected($customerId === $customer->id)>{{ $customer->code }}：{{ $customer->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="space-y-1">
                <label for="office_id" class="block text-xs font-medium text-slate-600">営業所で絞り込む</label>
                <select name="office_id" id="office_id"
                    class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm shadow-xs">
                    <option value="">すべて</option>
                    @foreach ($offices as $office)
                        <option value="{{ $office->id }}" @selected($officeId === $office->id)>{{ $office->name }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm hover:bg-slate-50">
                絞り込む
            </button>

            @if ($employeeId || $customerId || $officeId)
                <a href="{{ route('responses.index') }}" class="text-sm text-slate-600 underline underline-offset-2">解除</a>
            @endif
        </form>

        <a href="{{ route('responses.create') }}"
            class="ms-auto rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
            ＋ 新しい顧客の回答を追加
        </a>
    </div>

    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs text-slate-600">
                <tr>
                    <th class="px-3 py-2 font-medium">No.</th>
                    <th class="px-3 py-2 font-medium">顧客</th>
                    <th class="px-3 py-2 font-medium">営業所・拠点</th>
                    <th class="px-3 py-2 font-medium">作成担当者</th>
                    <th class="px-3 py-2 font-medium">実績データの出どころ（主）</th>
                    <th class="px-3 py-2 font-medium whitespace-nowrap">作成時間</th>
                    <th class="px-3 py-2 font-medium">自分以外に作成できる人</th>
                    <th class="px-3 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($responses as $response)
                    <tr class="hover:bg-slate-50">
                        <td class="px-3 py-2 text-slate-500">{{ $loop->iteration + ($responses->firstItem() - 1) }}</td>
                        <td class="px-3 py-2">
                            <span class="block font-medium">{{ $response->customer?->name }}</span>
                            <span class="text-xs text-slate-500">{{ $response->customer?->code }}</span>
                        </td>
                        <td class="px-3 py-2">{{ $response->office?->name ?? '—' }}</td>
                        <td class="px-3 py-2">{{ $response->employee?->name ?? '—' }}</td>
                        <td class="px-3 py-2">{{ $catalog->label($response->data_source_primary_option_id) ?? '—' }}</td>
                        <td class="px-3 py-2 whitespace-nowrap">
                            {{ $response->creation_minutes !== null ? $response->creation_minutes.' 分' : '—' }}
                        </td>
                        <td class="px-3 py-2">{{ $catalog->label($response->dependency_option_id) ?? '—' }}</td>
                        <td class="px-3 py-2 text-right whitespace-nowrap">
                            <a href="{{ route('responses.edit', $response) }}"
                                class="text-slate-600 underline underline-offset-2 hover:text-slate-900">編集</a>

                            <form method="POST" action="{{ route('responses.destroy', $response) }}" class="ms-2 inline"
                                onsubmit="return confirm('この回答を削除します。よろしいですか？');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-rose-600 underline underline-offset-2 hover:text-rose-800">削除</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-3 py-10 text-center text-slate-500">
                            まだ回答がありません。「＋ 新しい顧客の回答を追加」から登録してください。
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $responses->links() }}
    </div>
</x-layout>
