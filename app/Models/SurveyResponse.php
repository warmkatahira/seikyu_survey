<?php

namespace App\Models;

use App\Support\ChoiceCatalog;
use Database\Factories\SurveyResponseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'employee_id',
    'customer_id',
    'billing_category',
    'office_id',
    'invoice_composition_option_id',
    'customer_check_option_id',
    'creation_minutes',
    'dependency_option_id',
    'detail_mailing_option_id',
    'detail_record_timing_other',
    'detail_record_timing_option_id',
    'detail_creation_minutes',
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
        'tools' => 'ツールの使用について',
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
     * The 請求書の構成 option (`invoice_composition` value) meaning there is no 明細, so
     * 明細について is neither asked nor kept.
     */
    public const COVER_ONLY = 'cover_only';

    /**
     * The 鑑 and 明細 作成時間 columns, summed into the 作成時間 totals.
     *
     * @var list<string>
     */
    public const MINUTES_COLUMNS = ['creation_minutes', 'detail_creation_minutes'];

    /**
     * Every answered column, in form order.
     *
     * The form, the validation rules and the exports all read from this one definition.
     * A `category` points at the `choice_categories.key` supplying that column's dropdown;
     * columns without one are free input of the given `type`. `choices` is a multi-select
     * whose name is not a column but an accessor over the pivot table (see choices()),
     * saved through syncChoices(); ticking its その他 option (value `other`) asks what it is
     * in a text input named after the field with an `_other` suffix (see otherInputName()).
     *
     * `hint` explains the question; `note` (optional) calls out an exception or an easy-to-miss
     * case, shown apart from the hint. Every question must be answered (a multi-select with at
     * least one tick) unless marked `optional`; a 明細 question only while it is asked, i.e.
     * unless 請求書の構成 is 鑑のみ. See isRequired().
     *
     * `except` lists option values of the shared list this question does not offer, e.g.
     * 作成した明細から参照 is a 鑑 data source only.
     *
     * `exclusive` (multi-selects) is the value of an option that stands alone, e.g. 使用していない:
     * ticking it clears the other ticks and vice versa, and validation rejects it with others.
     *
     * `asked_if` makes a follow-up question: [the one-answer field it hangs on, the option values
     * of that field it is asked for]. It shows, is required and is kept only while one of those
     * is chosen. See askedGiven().
     *
     * @var array<string, array{section: string, label: string, type: string, category?: string, hint?: string, note?: string, optional?: bool, except?: list<string>, exclusive?: string, asked_if?: array{string, list<string>}}>
     */
    public const FIELDS = [
        'invoice_composition_option_id' => [
            'section' => 'basic',
            'label' => '請求書の構成',
            'type' => 'choice',
            'category' => 'invoice_composition',
            'hint' => '鑑だけを作成しているのか、明細も作成しているのかをお答えください。',
            'note' => '「鑑のみ」を選ぶと「明細について」は非表示になり、回答も不要です。',
        ],
        'customer_check_option_id' => [
            'section' => 'basic',
            'label' => '送付前のお客様確認',
            'type' => 'choice',
            'category' => 'yes_no',
            'hint' => '請求書を送付する前に、金額や内容をお客様に確認してもらっているかをお答えください。',
        ],
        'dependency_option_id' => [
            'section' => 'basic',
            'label' => '自分以外に作成できる人',
            'type' => 'choice',
            'category' => 'dependency',
            'hint' => 'ご自身が不在のとき、この請求書（鑑・明細）を作成できる人がいるかどうかです。',
        ],

        // 鑑について
        'data_source_option_ids' => [
            'section' => 'cover',
            'label' => '実績データの出どころ',
            'type' => 'choices',
            'category' => 'data_source',
            'hint' => '鑑の数量の根拠となるデータを、どこから持ってきているかをお答えください。使っているものをすべてチェックしてください。',
        ],
        'creation_minutes' => [
            'section' => 'cover',
            'label' => '作成時間（分）',
            'type' => 'number',
            'hint' => '鑑の作成にかかるおおよその時間を「分」でご記入ください。正確でなくて構いません。',
            'note' => '明細の作成時間は「明細について」に分けてご記入ください。',
        ],

        // 明細について
        'detail_item_ids' => [
            'section' => 'detail',
            'label' => '作成している明細',
            'type' => 'choices',
            'category' => 'billing_item',
            'hint' => '作成している明細を、すべてチェックしてください。',
            'note' => '入出庫をまとめて「荷役」として明細を作成している場合は、入庫・出庫の両方にチェックしてください。',
        ],
        'detail_format_option_ids' => [
            'section' => 'detail',
            'label' => '明細の形式',
            'type' => 'choices',
            'category' => 'detail_format',
            'hint' => '作成している明細の形式を、すべてチェックしてください。',
            'note' => '明細によって形式が違う場合は、使っている形式をすべてチェックしてください。',
        ],
        'detail_mailing_option_id' => [
            'section' => 'detail',
            'label' => '明細の郵送',
            'type' => 'choice',
            'category' => 'detail_mailing',
            'hint' => '明細を紙に印刷して、お客様に郵送しているかをお答えください。',
        ],
        'detail_unmailed_option_ids' => [
            'section' => 'detail',
            'label' => '郵送していない明細の扱い',
            'type' => 'choices',
            'category' => 'detail_unmailed',
            'hint' => '郵送していない明細を、お客様へどのように渡しているかをお答えください。当てはまるものをすべてチェックしてください。',
            'asked_if' => ['detail_mailing_option_id', ['none', 'partly']],
        ],
        'detail_data_source_option_ids' => [
            'section' => 'detail',
            'label' => '実績データの出どころ',
            'type' => 'choices',
            'category' => 'data_source',
            'except' => ['from_detail'],
            'hint' => '明細に載せる数量の根拠となるデータを、どこから持ってきているかをお答えください。使っているものをすべてチェックしてください。',
        ],
        'detail_record_timing_option_id' => [
            'section' => 'detail',
            'label' => '実績の記録タイミング',
            'type' => 'choice',
            'category' => 'record_timing',
            'other' => true,
        ],
        'detail_creation_minutes' => [
            'section' => 'detail',
            'label' => '作成時間（分）',
            'type' => 'number',
            'hint' => '明細の集計・作成にかかるおおよその時間を「分」でご記入ください。',
            'note' => '明細が複数ある場合は合計で構いません。',
        ],

        'tool_option_ids' => [
            'section' => 'tools',
            'label' => '現状使用しているツールについて',
            'type' => 'choices',
            'category' => 'tool',
            'exclusive' => 'none',
            'hint' => '請求書の作成に現在使っているツールをすべてチェックしてください。',
        ],

        'digitization_request_option_id' => [
            'section' => 'customer_request',
            'label' => '請求書の電子化の要望',
            'type' => 'choice',
            'category' => 'digitization_request',
            'hint' => '弊社から出している請求書を電子化してほしい、という話を顧客から受けたことがあるかどうかです。ここでの電子化とは、紙の請求書を郵送する代わりに、PDFをメールで送る、顧客のシステムやWeb上で受け取ってもらうなど、紙を使わずに請求書をやり取りすることです。',
        ],
        'notes' => [
            'optional' => true,
            'section' => 'free_text',
            'label' => '困っていること・特記事項',
            'type' => 'textarea',
            'hint' => '選択肢に当てはまるものが無かった項目の補足も、こちらにご記入ください。',
        ],
    ];

    /**
     * Whether a question must be answered (a 明細 one only while 明細について is asked).
     */
    public static function isRequired(string $field): bool
    {
        return ! (self::FIELDS[$field]['optional'] ?? false);
    }

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
     * The その他 text input of a field asking what その他 means: for a multi-select it is
     * named after the field, e.g. 「detail_item_ids_other」; for a dropdown marked `other` it is
     * a column of its own, e.g. 「detail_record_timing_other」.
     */
    public static function otherInputName(string $field): string
    {
        return self::FIELDS[$field]['type'] === 'choices'
            ? "{$field}_other"
            : str_replace('_option_id', '_other', $field);
    }

    /**
     * The dropdowns that ask what その他 means in a column of their own, keyed by field name.
     *
     * @return array<string, array{section: string, label: string, type: string, category: string, other: true, hint?: string}>
     */
    public static function choiceFieldsWithOther(): array
    {
        return array_filter(self::choiceFields(), fn (array $field): bool => $field['other'] ?? false);
    }

    /**
     * Every input saved through syncChoices() rather than as a column: the multi-selects and
     * their その他 text inputs.
     *
     * @return list<string>
     */
    public static function choiceInputNames(): array
    {
        return collect(self::multiChoiceFields())->keys()
            ->flatMap(fn (string $field): array => [$field, self::otherInputName($field)])
            ->all();
    }

    /**
     * FIELDS grouped under their section, for rendering the form and the answer page.
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
     * Whether 請求書の構成 is 鑑のみ, so the answer has no 明細について.
     */
    public function isCoverOnly(): bool
    {
        return app(ChoiceCatalog::class)->value($this->invoice_composition_option_id) === self::COVER_ONLY;
    }

    /**
     * The answers with every 明細について question emptied when 請求書の構成 is 鑑のみ, so a
     * 明細 filled in before switching to 鑑のみ is not kept.
     *
     * @param  array<string, mixed>  $answers  validated input
     * @return array<string, mixed>
     */
    public static function withoutDetailIfCoverOnly(array $answers): array
    {
        $composition = $answers['invoice_composition_option_id'] ?? null;

        if (app(ChoiceCatalog::class)->value($composition === null ? null : (int) $composition) !== self::COVER_ONLY) {
            return $answers;
        }

        foreach (self::FIELDS as $field => $definition) {
            if ($definition['section'] !== 'detail') {
                continue;
            }

            $answers[$field] = $definition['type'] === 'choices' ? [] : null;

            if ($definition['type'] === 'choices' || ($definition['other'] ?? false)) {
                $answers[self::otherInputName($field)] = null;
            }
        }

        return $answers;
    }

    /**
     * Whether a question is asked given the answers so far: always, unless it is a follow-up
     * (`asked_if`) whose answer it hangs on is not one of the options it is asked for.
     *
     * @param  array<string, mixed>  $answers  input or attributes, keyed by field name
     */
    public static function askedGiven(string $field, array $answers): bool
    {
        if (! isset(self::FIELDS[$field]['asked_if'])) {
            return true;
        }

        [$parent, $values] = self::FIELDS[$field]['asked_if'];
        $optionId = $answers[$parent] ?? null;

        return in_array(app(ChoiceCatalog::class)->value(filled($optionId) ? (int) $optionId : null), $values, true);
    }

    /**
     * The follow-up questions an option of a one-answer field asks, e.g. 明細の郵送's
     * 一部郵送している asks 郵送していない明細の扱い.
     *
     * @return list<string>
     */
    public static function followUpsAskedBy(string $field, ?string $value): array
    {
        return array_keys(array_filter(
            self::FIELDS,
            fn (array $definition): bool => isset($definition['asked_if'])
                && $definition['asked_if'][0] === $field
                && in_array($value, $definition['asked_if'][1], true),
        ));
    }

    /**
     * Whether this answer was asked a question (see askedGiven()).
     */
    public function asks(string $field): bool
    {
        return self::askedGiven($field, $this->getAttributes());
    }

    /**
     * The answers with every follow-up question that was not asked emptied, so one filled in
     * before its answer was changed is not kept.
     *
     * @param  array<string, mixed>  $answers  validated input
     * @return array<string, mixed>
     */
    public static function withoutUnaskedFollowUps(array $answers): array
    {
        foreach (self::FIELDS as $field => $definition) {
            if (self::askedGiven($field, $answers)) {
                continue;
            }

            $answers[$field] = $definition['type'] === 'choices' ? [] : null;

            if ($definition['type'] === 'choices' || ($definition['other'] ?? false)) {
                $answers[self::otherInputName($field)] = null;
            }
        }

        return $answers;
    }

    /**
     * The answers with each dropdown's その他 text emptied unless その他 is what was chosen.
     *
     * @param  array<string, mixed>  $answers  validated input
     * @return array<string, mixed>
     */
    public static function withoutStrayOtherText(array $answers): array
    {
        $catalog = app(ChoiceCatalog::class);

        foreach (array_keys(self::choiceFieldsWithOther()) as $field) {
            $optionId = $answers[$field] ?? null;

            if ($catalog->value($optionId === null ? null : (int) $optionId) !== 'other') {
                $answers[self::otherInputName($field)] = null;
            }
        }

        return $answers;
    }

    /**
     * A dropdown's answer as shown, その他 followed by what it is, e.g. 「その他（PDF）」.
     */
    public function choiceLabel(string $field): ?string
    {
        $label = app(ChoiceCatalog::class)->label($this->{$field});

        if ($label === null || ! (self::FIELDS[$field]['other'] ?? false)) {
            return $label;
        }

        $otherText = $this->{self::otherInputName($field)};

        return filled($otherText) ? "{$label}（{$otherText}）" : $label;
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
     * Options ticked in every multi-select, told apart by the pivot's `field`: the FIELDS name
     * of the multi-select they were ticked in. A ticked その他 carries what it is as `other_text`.
     */
    public function choices(): BelongsToMany
    {
        return $this->belongsToMany(ChoiceOption::class, 'survey_response_choices')
            ->withPivot('field', 'other_text')
            ->orderBy('choice_options.sort_order');
    }

    /**
     * The option ids ticked in one multi-select field, in the dropdown's order.
     *
     * @return list<int>
     */
    public function selectedChoiceIds(string $field): array
    {
        return $this->selectedChoices($field)->modelKeys();
    }

    /**
     * What その他 means in one multi-select field, or null when その他 is not ticked.
     */
    public function otherText(string $field): ?string
    {
        return $this->selectedChoices($field)
            ->first(fn (ChoiceOption $option): bool => $option->value === 'other')
            ?->pivot->other_text;
    }

    /**
     * The labels ticked in one multi-select field, その他 followed by what it is,
     * e.g. ['保管', 'その他（梱包資材）'].
     *
     * @return list<string>
     */
    public function selectedChoiceLabels(string $field): array
    {
        return $this->selectedChoices($field)
            ->map(fn (ChoiceOption $option): string => filled($option->pivot->other_text)
                ? "{$option->label}（{$option->pivot->other_text}）"
                : $option->label)
            ->all();
    }

    /**
     * @return Collection<int, ChoiceOption>
     */
    private function selectedChoices(string $field): Collection
    {
        return $this->choices
            ->filter(fn (ChoiceOption $option): bool => $option->pivot->field === $field)
            ->values();
    }

    /**
     * Saves the ticked options of every multi-select field; a field left out is cleared.
     *
     * @param  array<string, mixed>  $answers  validated input
     */
    public function syncChoices(array $answers): void
    {
        $catalog = app(ChoiceCatalog::class);

        foreach (self::multiChoiceFields() as $field => $definition) {
            $otherId = $catalog->optionIdByValue($definition['category'], 'other');
            $otherText = $answers[self::otherInputName($field)] ?? null;

            $this->choices()->wherePivot('field', $field)->sync(
                collect($answers[$field] ?? [])->mapWithKeys(fn (int|string $id): array => [(int) $id => [
                    'field' => $field,
                    'other_text' => (int) $id === $otherId && filled($otherText) ? $otherText : null,
                ]])->all(),
            );
        }

        $this->unsetRelation('choices');
    }

    /**
     * @return Attribute<list<int>, never>
     */
    protected function toolOptionIds(): Attribute
    {
        return Attribute::get(fn (): array => $this->selectedChoiceIds('tool_option_ids'));
    }

    /**
     * @return Attribute<list<int>, never>
     */
    protected function detailFormatOptionIds(): Attribute
    {
        return Attribute::get(fn (): array => $this->selectedChoiceIds('detail_format_option_ids'));
    }

    /**
     * @return Attribute<list<int>, never>
     */
    protected function dataSourceOptionIds(): Attribute
    {
        return Attribute::get(fn (): array => $this->selectedChoiceIds('data_source_option_ids'));
    }

    /**
     * @return Attribute<list<int>, never>
     */
    protected function detailItemIds(): Attribute
    {
        return Attribute::get(fn (): array => $this->selectedChoiceIds('detail_item_ids'));
    }

    /**
     * @return Attribute<list<int>, never>
     */
    protected function detailUnmailedOptionIds(): Attribute
    {
        return Attribute::get(fn (): array => $this->selectedChoiceIds('detail_unmailed_option_ids'));
    }

    /**
     * @return Attribute<list<int>, never>
     */
    protected function detailDataSourceOptionIds(): Attribute
    {
        return Attribute::get(fn (): array => $this->selectedChoiceIds('detail_data_source_option_ids'));
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
     * Answers whose invoice can be made by nobody but the respondent.
     */
    #[Scope]
    protected function soleOwner(Builder $query, int $noneOnlyMeOptionId): Builder
    {
        return $query->where('dependency_option_id', $noneOnlyMeOptionId);
    }

    #[Scope]
    protected function withMasters(Builder $query): Builder
    {
        return $query->with(['employee', 'customer', 'office', 'choices']);
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
