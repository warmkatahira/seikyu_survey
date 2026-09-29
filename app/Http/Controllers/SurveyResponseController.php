<?php

namespace App\Http\Controllers;

use App\Http\Requests\SurveyResponseRequest;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Office;
use App\Models\SurveyResponse;
use App\Support\ChoiceCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class SurveyResponseController extends Controller
{
    /**
     * Session key holding the employee last answered as, so a respondent filling in
     * several customers in a row does not reselect their own name every time.
     */
    private const LAST_EMPLOYEE_KEY = 'survey.last_employee_id';

    public function __construct(private readonly ChoiceCatalog $catalog) {}

    public function index(Request $request): View
    {
        $employeeId = $request->integer('employee_id') ?: null;
        $officeId = $request->integer('office_id') ?: null;
        $customerId = $request->integer('customer_id') ?: null;

        $responses = SurveyResponse::query()
            ->withMasters()
            ->when($employeeId, fn ($query, $id) => $query->where('employee_id', $id))
            ->when($officeId, fn ($query, $id) => $query->where('office_id', $id))
            ->when($customerId, fn ($query, $id) => $query->where('customer_id', $id))
            ->orderBy('id')
            ->paginate(50)
            ->withQueryString();

        return view('responses.index', [
            'responses' => $responses,
            'employeesByOffice' => $this->employeesByOffice(),
            'offices' => Office::query()->active()->ordered()->get(),
            'customers' => Customer::query()->active()->ordered()->get(),
            'catalog' => $this->catalog,
            'employeeId' => $employeeId,
            'officeId' => $officeId,
            'customerId' => $customerId,
            'totals' => $this->totals(),
        ]);
    }

    public function create(Request $request): View
    {
        $employeeId = $request->integer('employee_id')
            ?: $request->session()->get(self::LAST_EMPLOYEE_KEY);

        // Respondents usually answer for their own office, so it starts out filled in.
        $response = new SurveyResponse([
            'employee_id' => $employeeId,
            'office_id' => $employeeId ? Employee::query()->whereKey($employeeId)->value('office_id') : null,
        ]);

        return view('responses.create', $this->formData($response));
    }

    public function store(SurveyResponseRequest $request): RedirectResponse
    {
        $response = SurveyResponse::create($request->validated());

        $request->session()->put(self::LAST_EMPLOYEE_KEY, $response->employee_id);

        return redirect()
            ->route('responses.create')
            ->with('status', "「{$response->customer->name}」の回答を登録しました。続けて次の顧客を入力できます。");
    }

    public function edit(SurveyResponse $response): View
    {
        return view('responses.edit', $this->formData($response));
    }

    public function update(SurveyResponseRequest $request, SurveyResponse $response): RedirectResponse
    {
        $response->update($request->validated());

        return redirect()
            ->route('responses.index')
            ->with('status', "「{$response->customer->name}」の回答を更新しました。");
    }

    public function destroy(SurveyResponse $response): RedirectResponse
    {
        $customerName = $response->customer->name;

        $response->delete();

        return redirect()
            ->route('responses.index')
            ->with('status', "「{$customerName}」の回答を削除しました。");
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(SurveyResponse $response): array
    {
        return [
            'response' => $response,
            'employeesByOffice' => $this->employeesByOffice(),
            'customers' => Customer::query()->active()->ordered()->get(),
            'offices' => Office::query()->active()->ordered()->get(),
            'catalog' => $this->catalog,
        ];
    }

    /**
     * Active employees grouped under their office's name, offices in their own display order
     * and anyone without an office last, for the <optgroup>s of the 回答者 dropdown.
     *
     * @return Collection<string, Collection<int, Employee>>
     */
    private function employeesByOffice(): Collection
    {
        return Employee::query()
            ->active()
            ->ordered()
            ->with('office')
            ->get()
            ->sortBy([
                fn (Employee $a, Employee $b) => ($a->office === null) <=> ($b->office === null),
                fn (Employee $a, Employee $b) => [$a->office?->sort_order, $a->office?->code]
                    <=> [$b->office?->sort_order, $b->office?->code],
            ])
            ->groupBy(fn (Employee $employee) => $employee->office?->name ?? '営業所未設定');
    }

    /**
     * @return array{answered: int, minutes: int, sole_owner: int}
     */
    private function totals(): array
    {
        $soleOwnerId = $this->catalog->optionIdByValue('dependency', 'none_only_me');

        return [
            'answered' => SurveyResponse::query()->count(),
            'minutes' => (int) SurveyResponse::query()->sum('creation_minutes'),
            'sole_owner' => $soleOwnerId === null
                ? 0
                : SurveyResponse::query()->where('dependency_option_id', $soleOwnerId)->count(),
        ];
    }
}
