<?php

namespace App\Http\Requests;

use App\Models\SurveyResponse;
use App\Support\ChoiceCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SurveyResponseRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(ChoiceCatalog $catalog): array
    {
        $rules = [
            'employee_id' => ['required', Rule::exists('employees', 'id')],
            'customer_id' => ['required', Rule::exists('customers', 'id')],
            'billing_category' => ['nullable', 'string', 'max:50'],
            'office_id' => ['required', Rule::exists('offices', 'id')],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];

        foreach (SurveyResponse::FIELDS as $field => $definition) {
            if ($definition['type'] === 'number') {
                $rules[$field] = [...$this->presence($field, $catalog), 'integer', 'min:0', 'max:9999'];
            }
        }

        foreach (SurveyResponse::choiceFields() as $field => $definition) {
            $rules[$field] = [...$this->presence($field, $catalog), Rule::in($catalog->optionIds($definition['category']))];
        }

        foreach (SurveyResponse::multiChoiceFields() as $field => $definition) {
            $rules[$field] = [...$this->presence($field, $catalog), 'array'];
            $rules["{$field}.*"] = ['distinct', Rule::in($catalog->optionIds($definition['category']))];
        }

        foreach ($this->otherFields() as $field => $definition) {
            $rules[SurveyResponse::otherInputName($field)] = [
                Rule::requiredIf(fn (): bool => $this->choseOther($field, $definition, $catalog)),
                'nullable',
                'string',
                'max:100',
            ];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return collect($this->otherFields())->keys()
            ->mapWithKeys(fn (string $field): array => [
                SurveyResponse::otherInputName($field).'.required' => '「その他」を選んだ場合は、その内容を入力してください。',
            ])
            ->all();
    }

    /**
     * Every field with a その他 text input: the multi-selects and the dropdowns marked `other`.
     *
     * @return array<string, array{section: string, label: string, type: string, category: string, hint?: string}>
     */
    private function otherFields(): array
    {
        return SurveyResponse::multiChoiceFields() + SurveyResponse::choiceFieldsWithOther();
    }

    /**
     * The presence rules of a question: `required` for a required one (a 明細 one only while
     * 明細について is asked), and `nullable` for one that may be left blank.
     *
     * @return list<mixed>
     */
    private function presence(string $field, ChoiceCatalog $catalog): array
    {
        if (! SurveyResponse::isRequired($field)) {
            return ['nullable'];
        }

        return SurveyResponse::FIELDS[$field]['section'] === 'detail'
            ? [Rule::requiredIf(fn (): bool => ! $this->isCoverOnly($catalog)), 'nullable']
            : ['required'];
    }

    /**
     * Whether 請求書の構成 was answered 鑑のみ, so 明細について is not asked.
     */
    private function isCoverOnly(ChoiceCatalog $catalog): bool
    {
        $coverOnlyId = $catalog->optionIdByValue('invoice_composition', SurveyResponse::COVER_ONLY);

        return $coverOnlyId !== null && (int) $this->input('invoice_composition_option_id') === $coverOnlyId;
    }

    /**
     * Whether その他 was chosen in a field, so what it is must be said. A 明細 question does not
     * count while 請求書の構成 is 鑑のみ, as its answers are thrown away on save.
     *
     * @param  array{section: string, type: string, category: string}  $definition
     */
    private function choseOther(string $field, array $definition, ChoiceCatalog $catalog): bool
    {
        if ($definition['section'] === 'detail' && $this->isCoverOnly($catalog)) {
            return false;
        }

        $otherId = $catalog->optionIdByValue($definition['category'], 'other');
        $chosen = array_map(intval(...), (array) $this->input($field, []));

        return $otherId !== null && in_array($otherId, $chosen, true);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $attributes = [
            'employee_id' => '請求書の作成担当者',
            'customer_id' => '顧客名',
            'billing_category' => '作成区分',
            'office_id' => '営業所・拠点',
        ];

        foreach (array_keys(SurveyResponse::FIELDS) as $field) {
            $attributes[$field] = $attributes["{$field}.*"] = SurveyResponse::columnLabel($field);
        }

        foreach (array_keys($this->otherFields()) as $field) {
            $attributes[SurveyResponse::otherInputName($field)] = SurveyResponse::columnLabel($field).'（その他の内容）';
        }

        return $attributes;
    }
}
