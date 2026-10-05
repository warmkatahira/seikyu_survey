<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 明細の形式 gains システムの出力をツールで加工, right after システムの出力をExcelで加工.
 */
return new class extends Migration
{
    public function up(): void
    {
        $categoryId = DB::table('choice_categories')->where('key', 'detail_format')->value('id');

        if ($categoryId === null || DB::table('choice_options')->where('choice_category_id', $categoryId)->where('value', 'system_output_tool')->exists()) {
            return;
        }

        $afterSort = (int) DB::table('choice_options')->where('choice_category_id', $categoryId)->where('value', 'system_output_edited')->value('sort_order');

        DB::table('choice_options')->where('choice_category_id', $categoryId)->where('sort_order', '>', $afterSort)->increment('sort_order', 10);

        DB::table('choice_options')->insert([
            'choice_category_id' => $categoryId,
            'value' => 'system_output_tool',
            'label' => 'システムの出力をツールで加工',
            'sort_order' => $afterSort + 10,
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
