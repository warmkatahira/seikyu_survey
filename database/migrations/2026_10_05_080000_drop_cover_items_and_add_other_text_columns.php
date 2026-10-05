<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 鑑に載せている項目 is dropped (its ticks go; the 請求項目 list stays for 作成している明細).
 * 明細の形式 and 実績の記録タイミング (鑑 and 明細) ask what その他 means, in a column each.
 * 明細の形式 loses 該当なし; if an answer already chose it, it is deactivated instead, so that
 * answer keeps showing it marked 「（無効）」.
 */
return new class extends Migration
{
    /**
     * Each その他 text column, after the dropdown it belongs to.
     *
     * @var array<string, string>
     */
    private const OTHER_COLUMNS = [
        'record_timing_other' => 'record_timing_option_id',
        'detail_format_other' => 'detail_format_option_id',
        'detail_record_timing_other' => 'detail_record_timing_option_id',
    ];

    public function up(): void
    {
        DB::table('survey_response_choices')->where('field', 'cover_item_ids')->delete();

        DB::table('choice_categories')->where('key', 'billing_item')->update([
            'description' => '「作成している明細」で使用します（複数選択）。',
        ]);

        Schema::table('survey_responses', function (Blueprint $table) {
            foreach (self::OTHER_COLUMNS as $column => $after) {
                $table->string($column, 100)->nullable()->after($after);
            }
        });

        $notApplicableId = DB::table('choice_options')
            ->join('choice_categories', 'choice_categories.id', '=', 'choice_options.choice_category_id')
            ->where('choice_categories.key', 'detail_format')
            ->where('choice_options.value', 'not_applicable')
            ->value('choice_options.id');

        if ($notApplicableId !== null) {
            DB::table('survey_responses')->where('detail_format_option_id', $notApplicableId)->exists()
                ? DB::table('choice_options')->where('id', $notApplicableId)->update(['is_active' => false, 'updated_at' => now()])
                : DB::table('choice_options')->where('id', $notApplicableId)->delete();
        }
    }

    /**
     * Drops the その他 columns only; ticked 鑑に載せている項目 and 該当なし are not restored.
     */
    public function down(): void
    {
        Schema::table('survey_responses', function (Blueprint $table) {
            $table->dropColumn(array_keys(self::OTHER_COLUMNS));
        });
    }
};
