<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * A ツールの使用について section asks which tools are used today (multi-select). Its ticks go
 * in survey_response_choices like every multi-select, so only the dropdown master is added.
 */
return new class extends Migration
{
    /** @var array<string, string> */
    private const OPTIONS = [
        'sagawa_freight' => '運賃算出ツール（佐川急便）',
        'yamato_freight' => '運賃算出ツール（ヤマト運輸）',
        'dedicated' => '専用ツール',
        'other' => 'その他',
    ];

    public function up(): void
    {
        if (DB::table('choice_categories')->where('key', 'tool')->exists()) {
            return;
        }

        $now = now();

        $categoryId = DB::table('choice_categories')->insertGetId([
            'key' => 'tool',
            'name' => '使用しているツール',
            'description' => '「現状使用しているツールについて」で使用します（複数選択）。',
            'sort_order' => 65,
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

    public function down(): void
    {
        DB::table('survey_response_choices')->where('field', 'tool_option_ids')->delete();

        $categoryId = DB::table('choice_categories')->where('key', 'tool')->value('id');

        if ($categoryId !== null) {
            DB::table('choice_options')->where('choice_category_id', $categoryId)->delete();
            DB::table('choice_categories')->where('id', $categoryId)->delete();
        }
    }
};
