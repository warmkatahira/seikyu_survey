<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 実績データの出どころ gains 作成した明細から参照, first in the list, for a 鑑 whose figures are
 * taken from the 明細 already made.
 */
return new class extends Migration
{
    public function up(): void
    {
        $categoryId = DB::table('choice_categories')->where('key', 'data_source')->value('id');

        if ($categoryId === null || DB::table('choice_options')->where('choice_category_id', $categoryId)->where('value', 'from_detail')->exists()) {
            return;
        }

        $firstSort = (int) DB::table('choice_options')->where('choice_category_id', $categoryId)->min('sort_order');

        DB::table('choice_options')->where('choice_category_id', $categoryId)->increment('sort_order', 10);

        DB::table('choice_options')->insert([
            'choice_category_id' => $categoryId,
            'value' => 'from_detail',
            'label' => '作成した明細から参照',
            'sort_order' => $firstSort,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Nothing is restored; rerun ChoiceSeeder from before this change for the options.
     */
    public function down(): void {}
};
