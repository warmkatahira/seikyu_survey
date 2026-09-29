<?php

namespace Database\Seeders;

use App\Models\ChoiceCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ChoiceSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Every dropdown from the original Excel 選択肢マスタ sheet.
     *
     * The `value` of each option is a stable key the application can aggregate on
     * (see the admin dashboard), while `label` is the wording respondents see and
     * administrators are free to reword.
     *
     * @var array<string, array{name: string, description?: string, options: array<string, string>}>
     */
    private const CATEGORIES = [
        'presence' => [
            'name' => '有無',
            'description' => '請求項目の構成（保管料・荷役料・運賃・作業/その他）で使用します。',
            'options' => [
                'yes' => 'あり',
                'no' => 'なし',
            ],
        ],
        'storage_billing_method' => [
            'name' => '保管料の課金方式',
            'options' => [
                'three_period_month_end' => '三期制（月末締め）',
                'three_period_20' => '三期制（20日締め）',
                'three_period_10' => '三期制（10日締め）',
                'daily_prorated' => '日割',
                'monthly_fixed' => '月額固定',
                'per_tsubo' => '坪建て',
                'per_pallet' => 'パレット建て',
                'per_piece' => '個建て（ピース）',
                'other' => 'その他',
                'none' => '保管料なし',
            ],
        ],
        'closing_day' => [
            'name' => '締め日',
            'options' => [
                'month_end' => '月末',
                'day_25' => '25日',
                'day_20' => '20日',
                'day_15' => '15日',
                'day_10' => '10日',
                'other' => 'その他',
            ],
        ],
        'detail_presence' => [
            'name' => '別紙明細の有無',
            'options' => [
                'all' => '全ての項目に添付',
                'partial' => '一部の項目のみ添付',
                'none' => '添付なし（鏡のみ）',
            ],
        ],
        'detail_format' => [
            'name' => '別紙明細の形式',
            'options' => [
                'excel_own' => 'Excel（自分で作った独自フォーマット）',
                'excel_shared' => 'Excel（部署内で共通のフォーマット）',
                'system_output_raw' => 'システムの出力をそのまま添付',
                'system_output_edited' => 'システムの出力をExcelで加工',
                'handwritten_scan' => '手書き・紙をスキャン',
                'customer_format' => '顧客指定の様式（PDF等）',
                'other' => 'その他',
                'not_applicable' => '該当なし',
            ],
        ],
        'data_source' => [
            'name' => '実績データの出どころ',
            'description' => '請求金額（数量）の根拠となるデータの入手元。主・副の両方で使用します。',
            'options' => [
                'wms' => '出荷システム（WMS）',
                'smooth' => '顧客管理システム（smooth）',
                'access_tool' => 'ACCESSの自作ツール',
                'excel_own' => 'Excelの自作管理表',
                'carrier_invoice' => '運送会社の請求データ・送り状データ',
                'site_daily_report' => '現場の日報・作業記録',
                'handwritten_slip' => '手書き伝票・納品書の控え',
                'customer_contact' => '顧客からのメール・電話連絡',
                'contract_fixed' => '契約書の固定額（実績の記録は不要）',
                'no_record' => '特に記録なし（記憶・都度確認）',
                'other' => 'その他',
                'not_applicable' => '該当なし',
            ],
        ],
        'record_timing' => [
            'name' => '実績の記録タイミング',
            'options' => [
                'daily' => '毎日その都度入力している',
                'weekly' => '数日〜週1回まとめて入力している',
                'month_end_documents' => '月末に書類を見ながら一括で入力している',
                'month_end_system' => '月末にシステムから抽出している',
                'fixed_no_record' => '固定額のため記録していない',
                'other' => 'その他',
            ],
        ],
        'price_basis' => [
            'name' => '単価の根拠',
            'options' => [
                'contract_accessible' => '契約書・覚書があり、すぐ確認できる',
                'contract_unknown_location' => '契約書はあるが、どこにあるか分からない',
                'verbal_only' => '口頭・メールのやりとりのみ',
                'handover_only' => '前任からの引き継ぎのみ（根拠書類なし）',
                'unknown' => 'わからない',
            ],
        ],
        'yes_no' => [
            'name' => 'はい／いいえ',
            'options' => [
                'yes' => 'はい',
                'no' => 'いいえ',
            ],
        ],
        'irregular_frequency' => [
            'name' => 'イレギュラー作業の発生頻度',
            'options' => [
                'monthly' => 'ほぼ毎月発生する',
                'few_months' => '数ヵ月に1回程度',
                'few_times_year' => '年に数回程度',
                'rarely' => 'ほとんど発生しない',
            ],
        ],
        'dependency' => [
            'name' => '自分以外に作成できる人',
            'description' => '属人度。「いない（自分しか作れない）」の件数は管理ダッシュボードで集計します。',
            'options' => [
                'two_or_more' => 'いる（2人以上）',
                'only_one' => 'いる（1人だけ）',
                'none_only_me' => 'いない（自分しか作れない）',
                'unknown' => 'わからない',
            ],
        ],
    ];

    public function run(): void
    {
        $categorySort = 0;

        foreach (self::CATEGORIES as $key => $definition) {
            $categorySort += 10;

            $category = ChoiceCategory::updateOrCreate(
                ['key' => $key],
                [
                    'name' => $definition['name'],
                    'description' => $definition['description'] ?? null,
                    'sort_order' => $categorySort,
                ],
            );

            $optionSort = 0;

            foreach ($definition['options'] as $value => $label) {
                $optionSort += 10;

                $category->options()->updateOrCreate(
                    ['value' => $value],
                    ['label' => $label, 'sort_order' => $optionSort, 'is_active' => true],
                );
            }
        }
    }
}
