<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChoiceCategory;
use App\Models\SurveyResponse;
use Illuminate\View\View;

class ChoiceCategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.choices.index', [
            'categories' => ChoiceCategory::query()->ordered()->with('options')->get(),
            'usedBy' => $this->fieldLabelsByCategory(),
        ]);
    }

    /**
     * Which answer columns each dropdown feeds, so an administrator editing an option
     * can see where the wording will show up.
     *
     * @return array<string, list<string>>
     */
    private function fieldLabelsByCategory(): array
    {
        $usedBy = [];

        foreach (SurveyResponse::choiceFields() as $definition) {
            $usedBy[$definition['category']][] = $definition['label'];
        }

        return $usedBy;
    }
}
