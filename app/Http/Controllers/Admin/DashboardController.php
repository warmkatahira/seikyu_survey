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
    /** @var list<string> */
    private const COVER_AND_DETAIL = ['鑑', '明細'];

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
            // Each breakdown: its count columns' headings, and a row per option with one count per column.
            'breakdowns' => [
                '作成している明細（複数選択）' => [['件数'], $this->tickedCounts(['detail_item_ids'], $catalog)],
                '郵送していない明細の扱い（複数選択）' => [['件数'], $this->tickedCounts(['detail_unmailed_option_ids'], $catalog)],
                '現状使用しているツール（複数選択）' => [['件数'], $this->tickedCounts(['tool_option_ids'], $catalog)],
                '実績データの出どころ（複数選択）' => [self::COVER_AND_DETAIL, $this->tickedCounts(['data_source_option_ids', 'detail_data_source_option_ids'], $catalog)],
                '自分以外に作成できる人' => [['件数'], $this->singleCounts('dependency_option_id', $catalog)],
                '実績の記録タイミング（明細）' => [['件数'], $this->singleCounts('detail_record_timing_option_id', $catalog)],
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
     * One question asked once per answer.
     *
     * @return Collection<int, object{label: string, counts: list<int>}>
     */
    private function singleCounts(string $column, ChoiceCatalog $catalog): Collection
    {
        return $this->optionRows($column, [$this->countsByOption($column)], $catalog);
    }

    /**
     * A row per option chosen in any of the counted columns, in the dropdown's order, with
     * (未回答) last.
     *
     * @param  list<Collection<string, int>>  $columns  counts by option id, one per count column
     * @return Collection<int, object{label: string, counts: list<int>}>
     */
    private function optionRows(string $field, array $columns, ChoiceCatalog $catalog): Collection
    {
        $order = array_flip($catalog->optionIds(SurveyResponse::FIELDS[$field]['category']));

        return collect($columns)
            ->flatMap(fn (Collection $counts): array => $counts->keys()->all())
            ->unique()
            ->sortBy(fn (string $optionId): int => $optionId === '' ? PHP_INT_MAX : ($order[(int) $optionId] ?? PHP_INT_MAX - 1))
            ->values()
            ->map(fn (string $optionId): object => (object) [
                'label' => $optionId === '' ? '(未回答)' : ($catalog->label((int) $optionId) ?? '(不明)'),
                'counts' => array_map(fn (Collection $counts): int => $counts->get($optionId, 0), $columns),
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
     * How many answers ticked each option of one or more multi-selects asked the same question
     * (the 鑑 one and the 明細 one), one count per field, every option listed even when nobody
     * ticked it.
     *
     * @param  list<string>  $fields
     * @return Collection<int, object{label: string, counts: list<int>}>
     */
    private function tickedCounts(array $fields, ChoiceCatalog $catalog): Collection
    {
        $counts = DB::table('survey_response_choices')
            ->selectRaw('field, choice_option_id, COUNT(*) as total')
            ->whereIn('field', $fields)
            ->groupBy('field', 'choice_option_id')
            ->get();
        $count = fn (string $field, int $optionId): int => (int) $counts
            ->first(fn (object $row): bool => $row->field === $field && (int) $row->choice_option_id === $optionId)
            ?->total;

        return $catalog->optionsIncluding(SurveyResponse::FIELDS[$fields[0]]['category'], $counts->pluck('choice_option_id')->all())
            ->map(fn (ChoiceOption $option): object => (object) [
                'label' => $option->label,
                'counts' => array_map(fn (string $field): int => $count($field, $option->id), $fields),
            ]);
    }
}
