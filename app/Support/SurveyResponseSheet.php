<?php

namespace App\Support;

use App\Models\SurveyResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\LazyCollection;

/**
 * The answers laid out in the column order of the original Excel 回答シート, shared by
 * the CSV and Excel downloads so both always carry the same columns.
 */
class SurveyResponseSheet
{
    public function __construct(private readonly ChoiceCatalog $catalog) {}

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return array_merge(
            ['No.', '顧客コード', '顧客名', '作成区分', '営業所・拠点', '請求書の作成担当者'],
            array_column(SurveyResponse::FIELDS, 'label'),
            ['登録日時', '更新日時'],
        );
    }

    /**
     * Numbers stay numbers (No. and 作成時間), so Excel can sum and sort them.
     *
     * @param  Builder<SurveyResponse>  $query
     * @return LazyCollection<int, list<string|int>>
     */
    public function rows(Builder $query): LazyCollection
    {
        return $query
            ->withMasters()
            ->orderBy('id')
            ->cursor()
            ->values()
            ->map(function (SurveyResponse $response, int $index): array {
                $row = [
                    $index + 1,
                    $response->customer?->code ?? '',
                    $response->customer?->name ?? '',
                    (string) $response->billing_category,
                    $response->office?->name ?? '',
                    $response->employee?->name ?? '',
                ];

                foreach (SurveyResponse::FIELDS as $field => $definition) {
                    $row[] = match ($definition['type']) {
                        'choice' => $this->catalog->label($response->{$field}) ?? '',
                        'number' => $response->{$field} ?? '',
                        default => (string) ($response->{$field} ?? ''),
                    };
                }

                $row[] = $response->created_at?->format('Y-m-d H:i') ?? '';
                $row[] = $response->updated_at?->format('Y-m-d H:i') ?? '';

                return $row;
            });
    }
}
