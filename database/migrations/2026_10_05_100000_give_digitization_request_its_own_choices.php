<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 請求書の電子化の要望 gains 「既に電子化済み」, so it no longer shares the はい／いいえ master with
 * other questions but gets its own (はい・いいえ・既に電子化済み). Existing はい／いいえ answers
 * move to the matching new option.
 */
return new class extends Migration
{
    /** @var array<string, string> */
    private const OPTIONS = [
        'yes' => 'はい',
        'no' => 'いいえ',
        'already_digital' => '既に電子化済み',
    ];

    public function up(): void
    {
        $categoryId = DB::table('choice_categories')->where('key', 'digitization_request')->value('id');

        if ($categoryId === null) {
            $now = now();

            $categoryId = DB::table('choice_categories')->insertGetId([
                'key' => 'digitization_request',
                'name' => '請求書の電子化の要望',
                'sort_order' => 75,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $sort = 0;

            foreach (self::OPTIONS as $value => $label) {
                DB::table('choice_options')->insert([
                    'choice_category_id' => $categoryId,
                    'value' => $value,
                    'label' => $label,
                    'sort_order' => $sort += 10,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $this->moveAnswers('yes_no', 'digitization_request');
    }

    public function down(): void
    {
        $this->moveAnswers('digitization_request', 'yes_no');

        // 既に電子化済み has no はい／いいえ counterpart, so those answers are emptied.
        $categoryId = DB::table('choice_categories')->where('key', 'digitization_request')->value('id');

        if ($categoryId !== null) {
            $optionIds = DB::table('choice_options')->where('choice_category_id', $categoryId)->pluck('id');
            DB::table('survey_responses')->whereIn('digitization_request_option_id', $optionIds)->update(['digitization_request_option_id' => null]);
            DB::table('choice_options')->where('choice_category_id', $categoryId)->delete();
            DB::table('choice_categories')->where('id', $categoryId)->delete();
        }
    }

    /**
     * Points each はい／いいえ answer at the option with the same value in the other category.
     */
    private function moveAnswers(string $fromKey, string $toKey): void
    {
        $from = $this->optionIds($fromKey);
        $to = $this->optionIds($toKey);

        foreach (['yes', 'no'] as $value) {
            if (isset($from[$value], $to[$value])) {
                DB::table('survey_responses')->where('digitization_request_option_id', $from[$value])
                    ->update(['digitization_request_option_id' => $to[$value]]);
            }
        }
    }

    /**
     * @return array<string, int> option id by value
     */
    private function optionIds(string $categoryKey): array
    {
        return DB::table('choice_options')
            ->join('choice_categories', 'choice_categories.id', '=', 'choice_options.choice_category_id')
            ->where('choice_categories.key', $categoryKey)
            ->pluck('choice_options.id', 'choice_options.value')
            ->all();
    }
};
