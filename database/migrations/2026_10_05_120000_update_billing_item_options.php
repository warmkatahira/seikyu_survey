<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 作成している明細 offers 入庫, 出庫, 梱包 and 資材 in place of 荷役
 * (保管・入庫・出庫・梱包・資材・運賃・作業・その他).
 * 荷役 already ticked by an answer is deactivated instead of deleted, so that answer keeps
 * showing it marked 「（無効）」.
 */
return new class extends Migration
{
    /**
     * Every option in display order; values missing from the category are added.
     *
     * @var array<string, string>
     */
    private const OPTIONS = [
        'storage' => '保管',
        'inbound' => '入庫',
        'outbound' => '出庫',
        'packing' => '梱包',
        'materials' => '資材',
        'freight' => '運賃',
        'work' => '作業',
        'other' => 'その他',
    ];

    public function up(): void
    {
        $categoryId = DB::table('choice_categories')->where('key', 'billing_item')->value('id');

        if ($categoryId === null) {
            return;
        }

        $now = now();
        $sort = 0;

        foreach (self::OPTIONS as $value => $label) {
            $sort += 10;
            $existing = DB::table('choice_options')->where('choice_category_id', $categoryId)->where('value', $value);

            $existing->exists()
                ? $existing->update(['sort_order' => $sort, 'updated_at' => $now])
                : DB::table('choice_options')->insert([
                    'choice_category_id' => $categoryId,
                    'value' => $value,
                    'label' => $label,
                    'sort_order' => $sort,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
        }

        $handlingId = DB::table('choice_options')->where('choice_category_id', $categoryId)->where('value', 'handling')->value('id');

        if ($handlingId !== null) {
            DB::table('survey_response_choices')->where('choice_option_id', $handlingId)->exists()
                ? DB::table('choice_options')->where('id', $handlingId)->update(['is_active' => false, 'updated_at' => $now])
                : DB::table('choice_options')->where('id', $handlingId)->delete();
        }
    }

    /**
     * Nothing is restored; rerun ChoiceSeeder from before this change for the options.
     */
    public function down(): void {}
};
