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
        'invoice_composition' => [
            'name' => '請求書の構成',
            'description' => '「鑑のみ」を選ぶと「明細について」は回答不要になります。',
            'options' => [
                'cover_only' => '鑑のみ',
                'cover_and_detail' => '鑑と明細',
            ],
        ],
        'billing_item' => [
            'name' => '請求項目',
            'description' => '「作成している明細」で使用します（複数選択）。',
            'options' => [
                'storage' => '保管',
                'inbound' => '入庫',
                'outbound' => '出庫',
                'packing' => '梱包',
                'materials' => '資材',
                'freight' => '運賃',
                'work' => '作業',
                'other' => 'その他',
            ],
        ],
        'detail_format' => [
            'name' => '別紙明細の形式',
            'options' => [
                'excel_own' => 'Excel（自分で作った独自フォーマット）',
                'excel_shared' => 'Excel（部署内で共通のフォーマット）',
                'system_output_raw' => 'システムの出力をそのまま添付',
                'system_output_edited' => 'システムの出力をExcelで加工',
                'system_output_tool' => 'システムの出力をツールで加工',
                'handwritten_scan' => '手書き',
                'customer_format' => '顧客指定の様式（PDF等）',
                'other' => 'その他',
            ],
        ],
        'detail_mailing' => [
            'name' => '明細の郵送',
            'options' => [
                'all' => '全て郵送している',
                'none' => '全て郵送していない',
                'partly' => '一部郵送している',
            ],
        ],
        'detail_unmailed' => [
            'name' => '郵送していない明細の扱い',
            'description' => '「明細の郵送」で「全て郵送していない」「一部郵送している」を選んだ場合に使用します（複数選択）。',
            'options' => [
                'email' => 'メールで送付',
                'fax_or_hand' => 'FAX・手渡し',
                'not_sent' => '送付していない',
                'other' => 'その他',
            ],
        ],
        'data_source' => [
            'name' => '実績データの出どころ',
            'description' => '請求金額（数量）の根拠となるデータの入手元。鑑・明細それぞれで使用します（複数選択）。',
            'options' => [
                'from_detail' => '作成した明細から参照',
                'wms' => '出荷システム（WMS）',
                'picking_list' => '出荷時のピッキングリスト',
                'excel_own' => 'Excelの自作管理表',
                'carrier_invoice' => '請求データ・請求書',
                'site_daily_report' => '現場の日報・作業記録',
                'handwritten_slip' => '手書き伝票・納品書の控え',
                'customer_contact' => '顧客からのメール・電話連絡',
                'no_record' => '特に記録なし（記憶・都度確認）',
                'other' => 'その他',
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
        'yes_no' => [
            'name' => 'はい／いいえ',
            'options' => [
                'yes' => 'はい',
                'no' => 'いいえ',
            ],
        ],
        'tool' => [
            'name' => '使用しているツール',
            'description' => '「現状使用しているツールについて」で使用します（複数選択）。',
            'options' => [
                'sagawa_freight' => '運賃算出ツール（佐川急便）',
                'yamato_freight' => '運賃算出ツール（ヤマト運輸）',
                'dedicated' => '専用ツール',
                'other' => 'その他',
            ],
        ],
        'digitization_request' => [
            'name' => '請求書の電子化の要望',
            'options' => [
                'yes' => 'はい',
                'no' => 'いいえ',
                'already_digital' => '既に電子化済み',
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
