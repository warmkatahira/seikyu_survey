<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Office;
use App\Models\SurveyResponse;
use App\Support\ChoiceCatalog;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(ChoiceCatalog $catalog): View
    {
        $soleOwnerId = $catalog->optionIdByValue('dependency', 'none_only_me');

        return view('admin.dashboard', [
            'answered' => SurveyResponse::query()->count(),
            'totalMinutes' => (int) SurveyResponse::query()->sum('creation_minutes'),
            'soleOwnerCount' => $soleOwnerId === null
                ? 0
                : SurveyResponse::query()->where('dependency_option_id', $soleOwnerId)->count(),
            'employeeCount' => Employee::query()->active()->count(),
            'customerCount' => Customer::query()->active()->count(),
            'officeCount' => Office::query()->active()->count(),
            'answeredCustomerCount' => SurveyResponse::query()->distinct()->count('customer_id'),
            'byEmployee' => $this->countsByEmployee(),
            'byDataSource' => $this->countsByChoice('data_source_primary_option_id', $catalog),
            'byDependency' => $this->countsByChoice('dependency_option_id', $catalog),
            'byRecordTiming' => $this->countsByChoice('record_timing_option_id', $catalog),
        ]);
    }

    /**
     * @return Collection<int, object{name: string, responses: int, minutes: int}>
     */
    private function countsByEmployee(): Collection
    {
        return SurveyResponse::query()
            ->selectRaw('employee_id, COUNT(*) as responses, COALESCE(SUM(creation_minutes), 0) as minutes')
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
     * @return Collection<int, object{label: string, count: int}>
     */
    private function countsByChoice(string $column, ChoiceCatalog $catalog): Collection
    {
        return SurveyResponse::query()
            ->selectRaw("{$column} as option_id, COUNT(*) as total")
            ->groupBy($column)
            ->orderByDesc('total')
            ->get()
            ->map(fn (SurveyResponse $row): object => (object) [
                'label' => $catalog->label($row->option_id) ?? '(未回答)',
                'count' => (int) $row->total,
            ]);
    }
}
