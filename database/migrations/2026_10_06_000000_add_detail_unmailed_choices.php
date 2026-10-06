<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 明細について asks how the 明細 that are not mailed reach the customer (multi-select), as a
 * follow-up of 明細の郵送 全て郵送していない／一部郵送している. Its ticks go in
 * survey_response_choices like every multi-select, so only the dropdown master is added.
 */
return new class extends Migration
{
    /** @var array<string, string> */
    private const OPTIONS = [
        'email' => 'メールで送付',
        'fax_or_hand' => 'FAX・手渡し',
        'not_sent' => '送付していない',
        'other' => 'その他',
    ];

    public function up(): void
    {
        if (DB::table('choice_categories')->where('key', 'detail_unmailed')->exists()) {
            return;
        }

        $now = now();

        $categoryId = DB::table('choice_categories')->insertGetId([
            'key' => 'detail_unmailed',
            'name' => '郵送していない明細の扱い',
            'description' => '「明細の郵送」で「全て郵送していない」「一部郵送している」を選んだ場合に使用します（複数選択）。',
            'sort_order' => 26,
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
        DB::table('survey_response_choices')->where('field', 'detail_unmailed_option_ids')->delete();

        $categoryId = DB::table('choice_categories')->where('key', 'detail_unmailed')->value('id');

        if ($categoryId !== null) {
            DB::table('choice_options')->where('choice_category_id', $categoryId)->delete();
            DB::table('choice_categories')->where('id', $categoryId)->delete();
        }
    }
};
