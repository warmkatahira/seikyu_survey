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
            'creation_minutes' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'detail_creation_minutes' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];

        foreach (SurveyResponse::choiceFields() as $field => $definition) {
            $rules[$field] = ['nullable', Rule::in($catalog->optionIds($definition['category']))];
        }

        foreach (SurveyResponse::multiChoiceFields() as $field => $definition) {
            $rules[$field] = ['nullable', 'array'];
            $rules["{$field}.*"] = ['distinct', Rule::in($catalog->optionIds($definition['category']))];
        }

        return $rules;
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

        return $attributes;
    }
}
