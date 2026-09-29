<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChoiceOptionRequest;
use App\Models\ChoiceCategory;
use App\Models\ChoiceOption;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;

class ChoiceOptionController extends Controller
{
    public function store(ChoiceOptionRequest $request, ChoiceCategory $category): RedirectResponse
    {
        $category->options()->create($request->validated());

        return back()->with('status', "「{$category->name}」に選択肢を追加しました。");
    }

    public function update(ChoiceOptionRequest $request, ChoiceOption $option): RedirectResponse
    {
        $option->update($request->validated());

        return back()->with('status', '選択肢を更新しました。');
    }

    public function destroy(ChoiceOption $option): RedirectResponse
    {
        try {
            $option->delete();
        } catch (QueryException) {
            return back()->with('error', 'この選択肢は既存の回答で使われているため削除できません。「有効」を外すと今後の回答では選べなくなります。');
        }

        return back()->with('status', '選択肢を削除しました。');
    }
}
