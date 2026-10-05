<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The 単価・作成方法 questions (単価の根拠, 前月ファイルのコピーで作成) and イレギュラー作業の発生頻度
 * are dropped from both 鑑 and 明細, together with the dropdown masters only they used. The
 * はい／いいえ master stays, as other questions still use it.
 */
return new class extends Migration
{
    /** @var list<string> */
    private const COLUMNS = [
        'price_basis_option_id',
        'copy_previous_month_option_id',
        'irregular_frequency_option_id',
        'detail_price_basis_option_id',
        'detail_copy_previous_month_option_id',
        'detail_irregular_frequency_option_id',
    ];

    /** @var list<string> */
    private const CATEGORIES = ['price_basis', 'irregular_frequency'];

    public function up(): void
    {
        Schema::table('survey_responses', function (Blueprint $table) {
            foreach (self::COLUMNS as $column) {
                $table->dropConstrainedForeignId($column);
            }
        });

        foreach (self::CATEGORIES as $key) {
            $categoryId = DB::table('choice_categories')->where('key', $key)->value('id');

            if ($categoryId !== null) {
                DB::table('choice_options')->where('choice_category_id', $categoryId)->delete();
                DB::table('choice_categories')->where('id', $categoryId)->delete();
            }
        }
    }

    /**
     * Restores the empty columns only; rerun ChoiceSeeder from before this change for the options.
     */
    public function down(): void
    {
        Schema::table('survey_responses', function (Blueprint $table) {
            foreach (self::COLUMNS as $column) {
                $table->foreignId($column)->nullable()->constrained('choice_options')->restrictOnDelete();
            }
        });
    }
};
