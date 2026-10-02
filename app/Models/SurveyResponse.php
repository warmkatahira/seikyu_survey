<?php

namespace App\Models;

use Database\Factories\SurveyResponseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'employee_id',
    'customer_id',
    'billing_category',
    'office_id',
    'closing_day_option_id',
    'data_source_primary_option_id',
    'data_source_secondary_option_id',
    'record_timing_option_id',
    'price_basis_option_id',
    'copy_previous_month_option_id',
    'creation_minutes',
    'irregular_frequency_option_id',
    'dependency_option_id',
    'detail_format_option_id',
    'detail_data_source_primary_option_id',
    'detail_data_source_secondary_option_id',
    'detail_record_timing_option_id',
    'detail_price_basis_option_id',
    'detail_copy_previous_month_option_id',
    'detail_creation_minutes',
    'detail_irregular_frequency_option_id',
    'detail_dependency_option_id',
    'digitization_request_option_id',
    'notes',
])]
class SurveyResponse extends Model
{
    /** @use HasFactory<SurveyResponseFactory> */
    use HasFactory;

    /**
     * Sections of the answer form, in display order.
     *
     * @var array<string, string>
     */
    public const SECTIONS = [
        'basic' => '基本情報',
        'cover' => '鑑について',
        'detail' => '明細について',
        'customer_request' => '顧客からの要望',
        'free_text' => '自由記述',
    ];

    /**
     * Short names of the sections asked about twice, put in front of a column's label wherever it
     * is shown outside its section (the export headings, validation messages).
     *
     * @var array<string, string>
     */
    public const SECTION_PREFIXES = [
        'cover' => '鑑',
        'detail' => '明細',
    ];

    /**
     * Sub-headings inside a section. 鑑について and 明細について ask the same three groups.
     *
     * @var array<string, string>
     */
    public const GROUPS = [
        'data_source' => '実績データの取得方法',
        'pricing' => '単価・作成方法',
        'workload' => '工数・属人度',
    ];

    /**
     * The 鑑 and 明細 作成時間 columns, summed into the 作成時間 totals.
     *
     * @var list<string>
     */
    public const MINUTES_COLUMNS = ['creation_minutes', 'detail_creation_minutes'];

    /**
     * The 鑑 and 明細 自分以外に作成できる人 columns; an answer counts towards
     * 「自分しか作れない」 when either of them says so.
     *
     * @var list<string>
     */
    public const DEPENDENCY_COLUMNS = ['dependency_option_id', 'detail_dependency_option_id'];

    /**
     * Every answered column, in form order.
     *
     * The form, the validation rules and the exports all read from this one definition.
     * A `category` points at the `choice_categories.key` supplying that column's dropdown;
     * columns without one are free input of the given `type`. `choices` is a multi-select
     * whose name is not a column but an accessor over the pivot table (see billingItems()),
     * saved through syncChoices().
     *
     * @var array<string, array{section: string, group?: string, label: string, type: string, category?: string, hint?: string}>
     */
    public const FIELDS = [
        'closing_day_option_id' => [
            'section' => 'basic',
            'label' => '締め日',
            'type' => 'choice',
            'category' => 'closing_day',
        ],

        // 鑑について
        'cover_item_ids' => [
            'section' => 'cover',
            'label' => '鑑に載せている項目',
            'type' => 'choices',
            'category' => 'billing_item',
            'hint' => '請求書の鑑に載せている項目を、すべてチェックしてください。',
        ],
        'data_source_primary_option_id' => [
            'section' => 'cover',
            'group' => 'data_source',
            'label' => '実績データの出どころ（主）',
            'type' => 'choice',
            'category' => 'data_source',
            'hint' => '鑑の請求金額（数量）の根拠となるデータを、どこから持ってきているかをお答えください。主なものをこちらに選んでください。',
        ],
        'data_source_secondary_option_id' => [
            'section' => 'cover',
            'group' => 'data_source',
            'label' => '実績データの出どころ（副）',
            'type' => 'choice',
            'category' => 'data_source',
            'hint' => '主なもの以外にも使っているデータがあれば選んでください。',
        ],
        'record_timing_option_id' => [
            'section' => 'cover',
            'group' => 'data_source',
            'label' => '実績の記録タイミング',
            'type' => 'choice',
            'category' => 'record_timing',
            'hint' => '入出庫や作業の実績を、日々その都度記録しているのか、月末にまとめて書類を見ながら入力しているのかをお答えください。',
        ],
        'price_basis_option_id' => [
            'section' => 'cover',
            'group' => 'pricing',
            'label' => '単価の根拠',
            'type' => 'choice',
            'category' => 'price_basis',
        ],
        'copy_previous_month_option_id' => [
            'section' => 'cover',
            'group' => 'pricing',
            'label' => '前月ファイルのコピーで作成',
            'type' => 'choice',
            'category' => 'yes_no',
        ],
        'creation_minutes' => [
            'section' => 'cover',
            'group' => 'workload',
            'label' => '作成時間（分）',
            'type' => 'number',
            'hint' => '鑑の作成にかかるおおよその時間を「分」でご記入ください。明細の作成時間は「明細について」に分けてご記入ください。正確でなくて構いません。',
        ],
        'irregular_frequency_option_id' => [
            'section' => 'cover',
            'group' => 'workload',
            'label' => 'イレギュラー作業の発生頻度',
            'type' => 'choice',
            'category' => 'irregular_frequency',
        ],
        'dependency_option_id' => [
            'section' => 'cover',
            'group' => 'workload',
            'label' => '自分以外に作成できる人',
            'type' => 'choice',
            'category' => 'dependency',
            'hint' => 'ご自身が不在のとき、同じ鑑を作成できる人がいるかどうかです。',
        ],

        // 明細について
        'detail_item_ids' => [
            'section' => 'detail',
            'label' => '作成している明細',
            'type' => 'choices',
            'category' => 'billing_item',
            'hint' => '請求書に添付するために作成している明細を、すべてチェックしてください。明細を作成していない（鑑のみの）場合は、チェックせずにこのセクションを飛ばしてください。',
        ],
        'detail_format_option_id' => [
            'section' => 'detail',
            'label' => '明細の形式',
            'type' => 'choice',
            'category' => 'detail_format',
        ],
        'detail_data_source_primary_option_id' => [
            'section' => 'detail',
            'group' => 'data_source',
            'label' => '実績データの出どころ（主）',
            'type' => 'choice',
            'category' => 'data_source',
            'hint' => '明細に載せる数量の根拠となるデータを、どこから持ってきているかをお答えください。主なものをこちらに選んでください。',
        ],
        'detail_data_source_secondary_option_id' => [
            'section' => 'detail',
            'group' => 'data_source',
            'label' => '実績データの出どころ（副）',
            'type' => 'choice',
            'category' => 'data_source',
            'hint' => '主なもの以外にも使っているデータがあれば選んでください。',
        ],
        'detail_record_timing_option_id' => [
            'section' => 'detail',
            'group' => 'data_source',
            'label' => '実績の記録タイミング',
            'type' => 'choice',
            'category' => 'record_timing',
        ],
        'detail_price_basis_option_id' => [
            'section' => 'detail',
            'group' => 'pricing',
            'label' => '単価の根拠',
            'type' => 'choice',
            'category' => 'price_basis',
        ],
        'detail_copy_previous_month_option_id' => [
            'section' => 'detail',
            'group' => 'pricing',
            'label' => '前月ファイルのコピーで作成',
            'type' => 'choice',
            'category' => 'yes_no',
        ],
        'detail_creation_minutes' => [
            'section' => 'detail',
            'group' => 'workload',
            'label' => '作成時間（分）',
            'type' => 'number',
            'hint' => '明細の集計・作成にかかるおおよその時間を「分」でご記入ください。明細が複数ある場合は合計で構いません。',
        ],
        'detail_irregular_frequency_option_id' => [
            'section' => 'detail',
            'group' => 'workload',
            'label' => 'イレギュラー作業の発生頻度',
            'type' => 'choice',
            'category' => 'irregular_frequency',
        ],
        'detail_dependency_option_id' => [
            'section' => 'detail',
            'group' => 'workload',
            'label' => '自分以外に作成できる人',
            'type' => 'choice',
            'category' => 'dependency',
            'hint' => 'ご自身が不在のとき、同じ明細を作成できる人がいるかどうかです。',
        ],

        'digitization_request_option_id' => [
            'section' => 'customer_request',
            'label' => '請求書の電子化の要望',
            'type' => 'choice',
            'category' => 'yes_no',
            'hint' => '弊社から出している請求書を電子化してほしい、という話を顧客から受けたことがあるかどうかです。',
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
     * The subset of FIELDS answered by ticking several options, keyed by field name.
     *
     * @return array<string, array{section: string, label: string, type: string, category: string, hint?: string}>
     */
    public static function multiChoiceFields(): array
    {
        return array_filter(self::FIELDS, fn (array $field): bool => $field['type'] === 'choices');
    }

    /**
     * FIELDS grouped under their section, then under their sub-heading ('' for fields asked
     * before any sub-heading), for rendering the form and the answer page.
     *
     * @return array<string, array<string, array<string, array{section: string, group?: string, label: string, type: string, category?: string, hint?: string}>>>
     */
    public static function fieldsBySection(): array
    {
        $grouped = [];

        foreach (self::FIELDS as $name => $field) {
            $grouped[$field['section']][$field['group'] ?? ''][$name] = $field;
        }

        return $grouped;
    }

    /**
     * A column's label as shown away from its section, e.g. 「鑑：作成時間（分）」, so the
     * questions asked of both 鑑 and 明細 can be told apart.
     */
    public static function columnLabel(string $name): string
    {
        $field = self::FIELDS[$name];
        $prefix = self::SECTION_PREFIXES[$field['section']] ?? null;

        return $prefix === null ? $field['label'] : "{$prefix}：{$field['label']}";
    }

    /**
     * 鑑 and 明細 together, or null when neither was answered.
     */
    public function totalMinutes(): ?int
    {
        if ($this->creation_minutes === null && $this->detail_creation_minutes === null) {
            return null;
        }

        return (int) $this->creation_minutes + (int) $this->detail_creation_minutes;
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

    /**
     * Ticked 請求項目 (保管・荷役・運賃…) of every multi-select, told apart by the pivot's `part`:
     * the section of the field they were ticked in (cover = 鑑に載せている項目,
     * detail = 作成している明細).
     */
    public function billingItems(): BelongsToMany
    {
        return $this->belongsToMany(ChoiceOption::class, 'survey_response_billing_items')
            ->withPivot('part')
            ->orderBy('choice_options.sort_order');
    }

    /**
     * The option ids ticked in one multi-select field, in the dropdown's order.
     *
     * @return list<int>
     */
    public function selectedChoiceIds(string $field): array
    {
        $part = self::FIELDS[$field]['section'];

        return $this->billingItems
            ->filter(fn (ChoiceOption $option): bool => $option->pivot->part === $part)
            ->values()
            ->modelKeys();
    }

    /**
     * Saves the ticked options of every multi-select field; a field left out is cleared.
     *
     * @param  array<string, mixed>  $answers  validated input
     */
    public function syncChoices(array $answers): void
    {
        foreach (array_keys(self::multiChoiceFields()) as $field) {
            $part = self::FIELDS[$field]['section'];

            $this->billingItems()->wherePivot('part', $part)->sync(
                collect($answers[$field] ?? [])->mapWithKeys(fn (int|string $id): array => [(int) $id => ['part' => $part]])->all(),
            );
        }

        $this->unsetRelation('billingItems');
    }

    /**
     * @return Attribute<list<int>, never>
     */
    protected function coverItemIds(): Attribute
    {
        return Attribute::get(fn (): array => $this->selectedChoiceIds('cover_item_ids'));
    }

    /**
     * @return Attribute<list<int>, never>
     */
    protected function detailItemIds(): Attribute
    {
        return Attribute::get(fn (): array => $this->selectedChoiceIds('detail_item_ids'));
    }

    /**
     * SQL summing 鑑 and 明細 作成時間 over a group of answers, for the 作成時間 totals.
     */
    public static function minutesSumSql(): string
    {
        return collect(self::MINUTES_COLUMNS)
            ->map(fn (string $column): string => "COALESCE(SUM({$column}), 0)")
            ->implode(' + ');
    }

    /**
     * Answers where either 鑑 or 明細 can be made by nobody but the respondent.
     */
    #[Scope]
    protected function soleOwner(Builder $query, int $noneOnlyMeOptionId): Builder
    {
        return $query->where(function (Builder $query) use ($noneOnlyMeOptionId) {
            foreach (self::DEPENDENCY_COLUMNS as $column) {
                $query->orWhere($column, $noneOnlyMeOptionId);
            }
        });
    }

    #[Scope]
    protected function withMasters(Builder $query): Builder
    {
        return $query->with(['employee', 'customer', 'office', 'billingItems']);
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
            'detail_creation_minutes' => 'integer',
        ];
    }
}
