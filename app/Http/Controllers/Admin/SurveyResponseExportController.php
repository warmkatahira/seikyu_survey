<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SurveyResponse;
use App\Support\Csv;
use App\Support\SurveyResponseSheet;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SurveyResponseExportController extends Controller
{
    /**
     * Exports every answer in the column order of the original Excel 回答シート,
     * so the result can be dropped straight into the existing sheet for review.
     */
    public function __invoke(SurveyResponseSheet $sheet): StreamedResponse
    {
        return Csv::download(
            'survey_responses_'.now()->format('Ymd_His').'.csv',
            $sheet->headings(),
            $sheet->rows(SurveyResponse::query()),
        );
    }
}
