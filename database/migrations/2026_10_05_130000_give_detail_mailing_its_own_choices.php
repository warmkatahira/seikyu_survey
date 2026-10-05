<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 明細の郵送 is answered 全て郵送している／全て郵送していない／一部郵送している instead of
 * はい／いいえ, from a master of its own. Existing はい become 全て郵送している and いいえ
 * become 全て郵送していない.
 */
return new class extends Migration
{
    /** @var array<string, string> */
    private const OPTIONS = [
        'all' => '全て郵送している',
        'none' => '全て郵送していない',
        'partly' => '一部郵送している',
    ];

    /**
     * はい／いいえ value => the 明細の郵送 value it becomes.
     *
     * @var array<string, string>
     */
    private const FROM_YES_NO = ['yes' => 'all', 'no' => 'none'];

    public function up(): void
    {
        if (! DB::table('choice_categories')->where('key', 'detail_mailing')->exists()) {
            $now = now();

            $categoryId = DB::table('choice_categories')->insertGetId([
                'key' => 'detail_mailing',
                'name' => '明細の郵送',
                'sort_order' => 25,
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

        $this->moveAnswers('yes_no', 'detail_mailing', self::FROM_YES_NO);
    }

    public function down(): void
    {
        $this->moveAnswers('detail_mailing', 'yes_no', array_flip(self::FROM_YES_NO));

        // 一部郵送している has no はい／いいえ counterpart, so those answers are emptied.
        $categoryId = DB::table('choice_categories')->where('key', 'detail_mailing')->value('id');

        if ($categoryId !== null) {
            $optionIds = DB::table('choice_options')->where('choice_category_id', $categoryId)->pluck('id');
            DB::table('survey_responses')->whereIn('detail_mailing_option_id', $optionIds)->update(['detail_mailing_option_id' => null]);
            DB::table('choice_options')->where('choice_category_id', $categoryId)->delete();
            DB::table('choice_categories')->where('id', $categoryId)->delete();
        }
    }

    /**
     * @param  array<string, string>  $valueMap  value in $fromKey => value in $toKey
     */
    private function moveAnswers(string $fromKey, string $toKey, array $valueMap): void
    {
        $from = $this->optionIds($fromKey);
        $to = $this->optionIds($toKey);

        foreach ($valueMap as $fromValue => $toValue) {
            if (isset($from[$fromValue], $to[$toValue])) {
                DB::table('survey_responses')->where('detail_mailing_option_id', $from[$fromValue])
                    ->update(['detail_mailing_option_id' => $to[$toValue]]);
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
