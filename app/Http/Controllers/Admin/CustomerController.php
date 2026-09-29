<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MasterType;
use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(): View
    {
        return view('admin.customers.index', [
            'customers' => Customer::query()->ordered()->withCount('surveyResponses')->paginate(50),
            'master' => MasterType::Customers,
        ]);
    }

    public function create(): View
    {
        return view('admin.customers.form', ['customer' => new Customer(['is_active' => true])]);
    }

    public function store(CustomerRequest $request): RedirectResponse
    {
        Customer::create($request->validated());

        return redirect()->route('admin.customers.index')->with('status', '顧客を追加しました。');
    }

    public function edit(Customer $customer): View
    {
        return view('admin.customers.form', ['customer' => $customer]);
    }

    public function update(CustomerRequest $request, Customer $customer): RedirectResponse
    {
        $customer->update($request->validated());

        return redirect()->route('admin.customers.index')->with('status', '顧客を更新しました。');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        try {
            $customer->delete();
        } catch (QueryException) {
            return back()->with('error', 'この顧客には回答が登録されているため削除できません。「有効」を外して選択肢から外してください。');
        }

        return redirect()->route('admin.customers.index')->with('status', '顧客を削除しました。');
    }
}
