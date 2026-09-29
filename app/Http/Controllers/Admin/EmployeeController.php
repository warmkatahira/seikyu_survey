<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MasterType;
use App\Http\Controllers\Controller;
use App\Http\Requests\EmployeeRequest;
use App\Models\Employee;
use App\Models\Office;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(): View
    {
        return view('admin.employees.index', [
            'employees' => Employee::query()->ordered()->with('office')->withCount('surveyResponses')->paginate(50),
            'master' => MasterType::Employees,
        ]);
    }

    public function create(): View
    {
        return view('admin.employees.form', [
            'employee' => new Employee(['is_active' => true]),
            'offices' => Office::query()->active()->ordered()->get(),
        ]);
    }

    public function store(EmployeeRequest $request): RedirectResponse
    {
        Employee::create($request->validated());

        return redirect()->route('admin.employees.index')->with('status', '従業員を追加しました。');
    }

    public function edit(Employee $employee): View
    {
        return view('admin.employees.form', [
            'employee' => $employee,
            'offices' => Office::query()->active()->ordered()->get(),
        ]);
    }

    public function update(EmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $employee->update($request->validated());

        return redirect()->route('admin.employees.index')->with('status', '従業員を更新しました。');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        try {
            $employee->delete();
        } catch (QueryException) {
            return back()->with('error', 'この従業員には回答が登録されているため削除できません。「有効」を外して選択肢から外してください。');
        }

        return redirect()->route('admin.employees.index')->with('status', '従業員を削除しました。');
    }
}
