<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MasterType;
use App\Http\Controllers\Controller;
use App\Http\Requests\OfficeRequest;
use App\Models\Office;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OfficeController extends Controller
{
    public function index(): View
    {
        return view('admin.offices.index', [
            'offices' => Office::query()->ordered()->withCount('employees')->paginate(50),
            'master' => MasterType::Offices,
        ]);
    }

    public function create(): View
    {
        return view('admin.offices.form', ['office' => new Office(['is_active' => true])]);
    }

    public function store(OfficeRequest $request): RedirectResponse
    {
        Office::create($request->validated());

        return redirect()->route('admin.offices.index')->with('status', '営業所を追加しました。');
    }

    public function edit(Office $office): View
    {
        return view('admin.offices.form', ['office' => $office]);
    }

    public function update(OfficeRequest $request, Office $office): RedirectResponse
    {
        $office->update($request->validated());

        return redirect()->route('admin.offices.index')->with('status', '営業所を更新しました。');
    }

    public function destroy(Office $office): RedirectResponse
    {
        try {
            $office->delete();
        } catch (QueryException) {
            return back()->with('error', 'この営業所は従業員・回答から参照されているため削除できません。「有効」を外して非表示にしてください。');
        }

        return redirect()->route('admin.offices.index')->with('status', '営業所を削除しました。');
    }
}
