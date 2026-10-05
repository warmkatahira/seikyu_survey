<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 実績データの出どころ gains 出荷時のピッキングリスト (right after 出荷システム) and loses four
 * options. One already ticked by an answer is deactivated instead, so that answer keeps showing
 * it marked 「（無効）」.
 */
return new class extends Migration
{
    /** @var list<string> */
    private const REMOVED_VALUES = ['smooth', 'access_tool', 'contract_fixed', 'not_applicable'];

    public function up(): void
    {
        $categoryId = DB::table('choice_categories')->where('key', 'data_source')->value('id');

        if ($categoryId === null) {
            return;
        }

        $this->addPickingList($categoryId);

        $optionIds = DB::table('choice_options')
            ->where('choice_category_id', $categoryId)
            ->whereIn('value', self::REMOVED_VALUES)
            ->pluck('id');

        $usedIds = DB::table('survey_response_choices')->whereIn('choice_option_id', $optionIds)->distinct()->pluck('choice_option_id');

        DB::table('choice_options')->whereIn('id', $usedIds)->update(['is_active' => false, 'updated_at' => now()]);
        DB::table('choice_options')->whereIn('id', $optionIds->diff($usedIds))->delete();
    }

    /**
     * Nothing is restored; rerun ChoiceSeeder from before this change for the options.
     */
    public function down(): void {}

    private function addPickingList(int $categoryId): void
    {
        if (DB::table('choice_options')->where('choice_category_id', $categoryId)->where('value', 'picking_list')->exists()) {
            return;
        }

        $wmsSort = (int) DB::table('choice_options')->where('choice_category_id', $categoryId)->where('value', 'wms')->value('sort_order');

        DB::table('choice_options')->where('choice_category_id', $categoryId)->where('sort_order', '>', $wmsSort)->increment('sort_order', 10);

        DB::table('choice_options')->insert([
            'choice_category_id' => $categoryId,
            'value' => 'picking_list',
            'label' => '出荷時のピッキングリスト',
            'sort_order' => $wmsSort + 10,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
