<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SurveyResponse;
use App\Support\ChoiceCatalog;
use App\Support\Csv;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SurveyResponseExportController extends Controller
{
    /**
     * Exports every answer in the column order of the original Excel 回答シート,
     * so the result can be dropped straight into the existing sheet for review.
     */
    public function __invoke(ChoiceCatalog $catalog): StreamedResponse
    {
        $headings = array_merge(
            ['No.', '顧客コード', '顧客名', '営業所・拠点', '請求書の作成担当者'],
            array_column(SurveyResponse::FIELDS, 'label'),
            ['登録日時', '更新日時'],
        );

        $rows = SurveyResponse::query()
            ->withMasters()
            ->orderBy('id')
            ->cursor()
            ->values()
            ->map(function (SurveyResponse $response, int $index) use ($catalog): array {
                $row = [
                    $index + 1,
                    $response->customer?->code ?? '',
                    $response->customer?->name ?? '',
                    $response->office?->name ?? '',
                    $response->employee?->name ?? '',
                ];

                foreach (SurveyResponse::FIELDS as $field => $definition) {
                    $row[] = $definition['type'] === 'choice'
                        ? ($catalog->label($response->{$field}) ?? '')
                        : (string) ($response->{$field} ?? '');
                }

                $row[] = $response->created_at?->format('Y-m-d H:i') ?? '';
                $row[] = $response->updated_at?->format('Y-m-d H:i') ?? '';

                return $row;
            });

        return Csv::download('survey_responses_'.now()->format('Ymd_His').'.csv', $headings, $rows);
    }
}
