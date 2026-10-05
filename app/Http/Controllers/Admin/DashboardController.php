<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChoiceOption;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Office;
use App\Models\SurveyResponse;
use App\Support\ChoiceCatalog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(ChoiceCatalog $catalog): View
    {
        $soleOwnerId = $catalog->optionIdByValue('dependency', 'none_only_me');

        return view('admin.dashboard', [
            'answered' => SurveyResponse::query()->count(),
            'totalMinutes' => (int) SurveyResponse::query()->selectRaw(SurveyResponse::minutesSumSql().' as minutes')->value('minutes'),
            'soleOwnerCount' => $soleOwnerId === null ? 0 : SurveyResponse::query()->soleOwner($soleOwnerId)->count(),
            'employeeCount' => Employee::query()->active()->count(),
            'customerCount' => Customer::query()->active()->count(),
            'officeCount' => Office::query()->active()->count(),
            'answeredCustomerCount' => SurveyResponse::query()->distinct()->count('customer_id'),
            'byEmployee' => $this->countsByEmployee(),
            'breakdowns' => [
                '請求項目（鑑に載せている項目／作成している明細）' => $this->tickedCounts('cover_item_ids', 'detail_item_ids', $catalog),
                '実績データの出どころ（複数選択）' => $this->tickedCounts('data_source_option_ids', 'detail_data_source_option_ids', $catalog),
                '自分以外に作成できる人' => $this->coverAndDetailCounts('dependency_option_id', $catalog),
                '実績の記録タイミング' => $this->coverAndDetailCounts('record_timing_option_id', $catalog),
            ],
        ]);
    }

    /**
     * @return Collection<int, object{name: string, responses: int, minutes: int}>
     */
    private function countsByEmployee(): Collection
    {
        return SurveyResponse::query()
            ->selectRaw('employee_id, COUNT(*) as responses, '.SurveyResponse::minutesSumSql().' as minutes')
            ->with('employee')
            ->groupBy('employee_id')
            ->orderByDesc('responses')
            ->get()
            ->map(fn (SurveyResponse $row): object => (object) [
                'name' => $row->employee?->name ?? '(不明)',
                'responses' => (int) $row->responses,
                'minutes' => (int) $row->minutes,
            ]);
    }

    /**
     * One question asked of both 鑑 and 明細, counted side by side: a row per option chosen in
     * either, in the dropdown's order, with (未回答) last.
     *
     * @return Collection<int, object{label: string, cover: int, detail: int}>
     */
    private function coverAndDetailCounts(string $coverColumn, ChoiceCatalog $catalog): Collection
    {
        $cover = $this->countsByOption($coverColumn);
        $detail = $this->countsByOption("detail_{$coverColumn}");
        $order = array_flip($catalog->optionIds(SurveyResponse::FIELDS[$coverColumn]['category']));

        return collect($cover->keys())
            ->merge($detail->keys())
            ->unique()
            ->sortBy(fn (string $optionId): int => $optionId === '' ? PHP_INT_MAX : ($order[(int) $optionId] ?? PHP_INT_MAX - 1))
            ->values()
            ->map(fn (string $optionId): object => (object) [
                'label' => $optionId === '' ? '(未回答)' : ($catalog->label((int) $optionId) ?? '(不明)'),
                'cover' => $cover->get($optionId, 0),
                'detail' => $detail->get($optionId, 0),
            ]);
    }

    /**
     * How many answers chose each option of one column, keyed by option id ('' for unanswered).
     *
     * @return Collection<string, int>
     */
    private function countsByOption(string $column): Collection
    {
        return SurveyResponse::query()
            ->selectRaw("{$column} as option_id, COUNT(*) as total")
            ->groupBy($column)
            ->pluck('total', 'option_id')
            ->mapWithKeys(fn (int|string $total, int|string $optionId): array => [(string) $optionId => (int) $total]);
    }

    /**
     * One multi-select asked of both 鑑 and 明細: how many answers ticked each option in either,
     * every option listed even when nobody ticked it.
     *
     * @return Collection<int, object{label: string, cover: int, detail: int}>
     */
    private function tickedCounts(string $coverField, string $detailField, ChoiceCatalog $catalog): Collection
    {
        $counts = DB::table('survey_response_choices')
            ->selectRaw('field, choice_option_id, COUNT(*) as total')
            ->whereIn('field', [$coverField, $detailField])
            ->groupBy('field', 'choice_option_id')
            ->get();
        $count = fn (string $field, int $optionId): int => (int) $counts
            ->first(fn (object $row): bool => $row->field === $field && (int) $row->choice_option_id === $optionId)
            ?->total;

        return $catalog->optionsIncluding(SurveyResponse::FIELDS[$coverField]['category'], $counts->pluck('choice_option_id')->all())
            ->map(fn (ChoiceOption $option): object => (object) [
                'label' => $option->label,
                'cover' => $count($coverField, $option->id),
                'detail' => $count($detailField, $option->id),
            ]);
    }
}
