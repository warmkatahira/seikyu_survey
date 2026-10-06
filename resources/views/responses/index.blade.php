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
        <form method="GET" action="{{ route('responses.index') }}" class="flex flex-wrap items-end gap-3" data-live-filter>
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
                        <option value="{{ $customer->id }}" @selected($customerId === $customer->id)>{{ $customer->name }}</option>
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

            @if ($employeeId || $customerId || $officeId)
                <a href="{{ route('responses.index') }}" class="pb-2 text-sm text-slate-600 underline underline-offset-2">解除</a>
            @endif
        </form>

        <a href="{{ route('responses.export', array_filter(['employee_id' => $employeeId, 'customer_id' => $customerId, 'office_id' => $officeId])) }}"
            class="ms-auto rounded-md border border-slate-300 bg-white px-4 py-2 text-sm hover:bg-slate-50"
            title="いま表示している条件の回答を、全項目入りのExcelファイルで出力します">
            {{ $employeeId || $customerId || $officeId ? '絞り込み結果をExcelで出力' : 'Excelで出力' }}
        </a>

        <a href="{{ route('responses.create') }}"
            class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
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
                    <th class="px-3 py-2 font-medium whitespace-nowrap">作成時間</th>
                    <th class="px-3 py-2 font-medium whitespace-nowrap">回答日時</th>
                    <th class="px-3 py-2 font-medium whitespace-nowrap">更新日時</th>
                    <th class="px-3 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($responses as $response)
                    <tr class="cursor-pointer transition-colors hover:bg-emerald-100" data-href="{{ route('responses.show', $response) }}">
                        <td class="px-3 py-2 text-slate-500">{{ $loop->iteration + ($responses->firstItem() - 1) }}</td>
                        <td class="px-3 py-2">
                            <a href="{{ route('responses.show', $response) }}" class="font-medium hover:underline">{{ $response->customer?->name }}</a>
                            @if (filled($response->billing_category))
                                <span class="ms-1 rounded bg-slate-100 px-1.5 py-0.5 text-xs text-slate-600">{{ $response->billing_category }}</span>
                            @endif
                        </td>
                        <td class="px-3 py-2">{{ $response->office?->name ?? '—' }}</td>
                        <td class="px-3 py-2">{{ $response->employee?->name ?? '—' }}</td>
                        <td class="px-3 py-2 whitespace-nowrap">
                            @if ($response->totalMinutes() === null)
                                —
                            @else
                                {{ $response->totalMinutes() }} 分
                                <span class="block text-xs text-slate-500">鑑 {{ $response->creation_minutes ?? '—' }}／明細 {{ $response->detail_creation_minutes ?? '—' }}</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap text-slate-600">{{ $response->created_at?->format('Y/m/d H:i') ?? '—' }}</td>
                        <td class="px-3 py-2 whitespace-nowrap text-slate-600">{{ $response->updated_at?->format('Y/m/d H:i') ?? '—' }}</td>
                        <td class="px-3 py-2 text-right whitespace-nowrap">
                            <div class="inline-flex items-center gap-2">
                                <x-edit-button :href="route('responses.edit', $response)" />
                                <x-delete-button :action="route('responses.destroy', $response)" confirm="この回答を削除します。よろしいですか？" />
                            </div>
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

    @push('scripts')
        <script>
            // The list follows the filters as soon as one is changed; there is no 絞り込む button.
            // Only the filters actually set go into the address, so it stays short and shareable.
            document.querySelector('[data-live-filter]')?.addEventListener('change', (event) => {
                const form = event.currentTarget;
                const query = new URLSearchParams([...new FormData(form)].filter(([, value]) => value !== ''));

                window.location.assign(query.size ? `${form.action}?${query}` : form.action);
            });

            // A click anywhere on a row opens that answer, except on its own links and buttons,
            // or when the click was the end of dragging to select text for copying.
            document.querySelectorAll('tr[data-href]').forEach((row) => {
                row.addEventListener('click', (event) => {
                    if (! event.target.closest('a, button, form') && window.getSelection().isCollapsed) {
                        window.location.href = row.dataset.href;
                    }
                });
            });
        </script>
    @endpush
</x-layout>
