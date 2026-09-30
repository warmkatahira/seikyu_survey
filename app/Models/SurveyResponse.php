<?php

namespace App\Models;

use Database\Factories\SurveyResponseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'employee_id',
    'customer_id',
    'billing_category',
    'office_id',
    'storage_fee_option_id',
    'handling_fee_option_id',
    'freight_fee_option_id',
    'other_work_option_id',
    'closing_day_option_id',
    'detail_presence_option_id',
    'detail_format_option_id',
    'data_source_primary_option_id',
    'data_source_secondary_option_id',
    'record_timing_option_id',
    'price_basis_option_id',
    'copy_previous_month_option_id',
    'irregular_frequency_option_id',
    'dependency_option_id',
    'creation_minutes',
    'notes',
])]
class SurveyResponse extends Model
{
    /** @use HasFactory<SurveyResponseFactory> */
    use HasFactory;

    /**
     * Sections of the answer form, mirroring the grouped header of the original Excel sheet.
     *
     * @var array<string, string>
     */
    public const SECTIONS = [
        'basic' => '基本情報',
        'billing_items' => '請求項目の構成（請求書鑑に載せている項目）',
        'detail_sheet' => '別紙明細',
        'data_source' => '★実績データの取得方法',
        'pricing' => '単価・作成方法',
        'workload' => '★工数・属人度',
        'free_text' => '自由記述',
    ];

    /**
     * Every answered column, in the order the original Excel sheet asks for it.
     *
     * The form, the validation rules and the CSV export all read from this one definition.
     * A `category` points at the `choice_categories.key` supplying that column's dropdown;
     * columns without one are free input of the given `type`.
     *
     * @var array<string, array{section: string, label: string, type: string, category?: string, hint?: string}>
     */
    public const FIELDS = [
        'storage_fee_option_id' => [
            'section' => 'billing_items',
            'label' => '保管料',
            'type' => 'choice',
            'category' => 'presence',
        ],
        'handling_fee_option_id' => [
            'section' => 'billing_items',
            'label' => '荷役料',
            'type' => 'choice',
            'category' => 'presence',
        ],
        'freight_fee_option_id' => [
            'section' => 'billing_items',
            'label' => '運賃',
            'type' => 'choice',
            'category' => 'presence',
        ],
        'other_work_option_id' => [
            'section' => 'billing_items',
            'label' => '作業・その他',
            'type' => 'choice',
            'category' => 'presence',
        ],
        'closing_day_option_id' => [
            'section' => 'basic',
            'label' => '締め日',
            'type' => 'choice',
            'category' => 'closing_day',
        ],
        'detail_presence_option_id' => [
            'section' => 'detail_sheet',
            'label' => '別紙明細の有無',
            'type' => 'choice',
            'category' => 'detail_presence',
        ],
        'detail_format_option_id' => [
            'section' => 'detail_sheet',
            'label' => '別紙明細の形式',
            'type' => 'choice',
            'category' => 'detail_format',
        ],
        'data_source_primary_option_id' => [
            'section' => 'data_source',
            'label' => '実績データの出どころ（主）',
            'type' => 'choice',
            'category' => 'data_source',
            'hint' => '請求金額（数量）の根拠となるデータを、どこから持ってきているかをお答えください。主なものをこちらに選んでください。',
        ],
        'data_source_secondary_option_id' => [
            'section' => 'data_source',
            'label' => '実績データの出どころ（副）',
            'type' => 'choice',
            'category' => 'data_source',
            'hint' => '主なもの以外にも使っているデータがあれば選んでください。',
        ],
        'record_timing_option_id' => [
            'section' => 'data_source',
            'label' => '実績の記録タイミング',
            'type' => 'choice',
            'category' => 'record_timing',
            'hint' => '入出庫や作業の実績を、日々その都度記録しているのか、月末にまとめて書類を見ながら入力しているのかをお答えください。',
        ],
        'price_basis_option_id' => [
            'section' => 'pricing',
            'label' => '単価の根拠',
            'type' => 'choice',
            'category' => 'price_basis',
        ],
        'copy_previous_month_option_id' => [
            'section' => 'pricing',
            'label' => '前月ファイルのコピーで作成',
            'type' => 'choice',
            'category' => 'yes_no',
        ],
        'creation_minutes' => [
            'section' => 'workload',
            'label' => '1社あたりの作成時間（分）',
            'type' => 'number',
            'hint' => '明細の集計から鏡の完成までにかかるおおよその時間を「分」でご記入ください。正確でなくて構いません。',
        ],
        'irregular_frequency_option_id' => [
            'section' => 'workload',
            'label' => 'イレギュラー作業の発生頻度',
            'type' => 'choice',
            'category' => 'irregular_frequency',
        ],
        'dependency_option_id' => [
            'section' => 'workload',
            'label' => '自分以外に作成できる人',
            'type' => 'choice',
            'category' => 'dependency',
            'hint' => 'ご自身が不在のとき、同じ請求書を作成できる人がいるかどうかです。',
        ],
        'notes' => [
            'section' => 'free_text',
            'label' => '困っていること・特記事項',
            'type' => 'textarea',
            'hint' => '選択肢に当てはまるものが無かった項目の補足も、こちらにご記入ください。',
        ],
    ];

    /**
     * The subset of FIELDS backed by `choice_options`, keyed by column name.
     *
     * @return array<string, array{section: string, label: string, type: string, category: string, hint?: string}>
     */
    public static function choiceFields(): array
    {
        return array_filter(self::FIELDS, fn (array $field): bool => $field['type'] === 'choice');
    }

    /**
     * FIELDS grouped under their section heading, for rendering the form.
     *
     * @return array<string, array<string, array{section: string, label: string, type: string, category?: string, hint?: string}>>
     */
    public static function fieldsBySection(): array
    {
        $grouped = [];

        foreach (self::FIELDS as $name => $field) {
            $grouped[$field['section']][$name] = $field;
        }

        return $grouped;
    }

    /**
     * The customer's name with the 作成区分 appended, e.g. 「株式会社ＡＡＡＡ（通販）」, so answers
     * for separately invoiced lines of one customer can be told apart at a glance.
     */
    public function customerLabel(): string
    {
        $name = $this->customer?->name ?? '';

        return filled($this->billing_category) ? "{$name}（{$this->billing_category}）" : $name;
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    #[Scope]
    protected function withMasters(Builder $query): Builder
    {
        return $query->with(['employee', 'customer', 'office']);
    }

    /**
     * Narrows to the 回答一覧 filters; a null filter is not applied.
     *
     * @param  array{employee_id: ?int, customer_id: ?int, office_id: ?int}  $filters
     */
    #[Scope]
    protected function filteredBy(Builder $query, array $filters): Builder
    {
        foreach ($filters as $column => $id) {
            $query->when($id, fn (Builder $query) => $query->where($column, $id));
        }

        return $query;
    }

    protected function casts(): array
    {
        return [
            'creation_minutes' => 'integer',
        ];
    }
}
