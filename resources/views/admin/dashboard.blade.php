<x-layout title="集計" heading="集計"
    subheading="回答の進み具合と、今後の進め方を決めるうえで効いてくる項目の内訳です。">

    <div class="mb-5 flex justify-end">
        <a href="{{ route('admin.responses.export') }}"
            class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
            回答をCSVでダウンロード
        </a>
    </div>

    <div class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        {{-- Each figure counts up from zero on load (data-count-up, resources/js/motion.js). --}}
        @foreach ([
            ['記入済みの顧客件数', [$answered], '件'],
            ['作成時間の合計', [$totalMinutes], '分'],
            ['うち「自分しか作れない」', [$soleOwnerCount], '件'],
            ['回答のあった顧客数', [$answeredCustomerCount, $customerCount], '社'],
        ] as [$statLabel, $statFigures, $statUnit])
            <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
                <p class="text-xs text-slate-500">{{ $statLabel }}</p>
                <p class="mt-1 text-2xl font-semibold">
                    @foreach ($statFigures as $figure)
                        @if (! $loop->first) / @endif<span data-count-up>{{ number_format($figure) }}</span>
                    @endforeach<span class="ms-1 text-sm font-normal text-slate-500">{{ $statUnit }}</span>
                </p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-5 lg:grid-cols-2">
        <x-section-card title="回答者別">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="text-left text-xs text-slate-600">
                        <tr>
                            <th class="py-2 font-medium">回答者</th>
                            <th class="py-2 text-right font-medium">件数</th>
                            <th class="py-2 text-right font-medium">作成時間の合計</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($byEmployee as $row)
                            <tr>
                                <td class="py-2">{{ $row->name }}</td>
                                <td class="py-2 text-right">{{ number_format($row->responses) }} 件</td>
                                <td class="py-2 text-right">{{ number_format($row->minutes) }} 分</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-6 text-center text-slate-500">まだ回答がありません。</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-section-card>

        @foreach ($breakdowns as $breakdownTitle => [$countHeadings, $breakdown])
            <x-section-card :title="$breakdownTitle">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="text-left text-xs text-slate-600">
                        <tr>
                            <th class="py-2 font-medium"></th>
                            @foreach ($countHeadings as $countHeading)
                                <th class="py-2 text-right font-medium">{{ $countHeading }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($breakdown as $row)
                            <tr>
                                <td class="py-2">{{ $row->label }}</td>
                                @foreach ($row->counts as $count)
                                    <td class="py-2 ps-3 text-right whitespace-nowrap">{{ number_format($count) }} 件</td>
                                @endforeach
                            </tr>
                        @empty
                            <tr><td colspan="{{ count($countHeadings) + 1 }}" class="py-6 text-center text-slate-500">まだ回答がありません。</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-section-card>
        @endforeach
    </div>

    <div class="mt-5 grid gap-3 sm:grid-cols-3">
        <a href="{{ route('admin.offices.index') }}" class="rounded-lg border border-slate-200 bg-white px-4 py-3 hover:bg-slate-50">
            <span class="block text-sm font-medium">営業所マスタ</span>
            <span class="text-xs text-slate-500">{{ number_format($officeCount) }} 件（有効）</span>
        </a>
        <a href="{{ route('admin.employees.index') }}" class="rounded-lg border border-slate-200 bg-white px-4 py-3 hover:bg-slate-50">
            <span class="block text-sm font-medium">従業員マスタ</span>
            <span class="text-xs text-slate-500">{{ number_format($employeeCount) }} 件（有効）</span>
        </a>
        <a href="{{ route('admin.customers.index') }}" class="rounded-lg border border-slate-200 bg-white px-4 py-3 hover:bg-slate-50">
            <span class="block text-sm font-medium">顧客マスタ</span>
            <span class="text-xs text-slate-500">{{ number_format($customerCount) }} 件（有効）</span>
        </a>
    </div>
</x-layout>
