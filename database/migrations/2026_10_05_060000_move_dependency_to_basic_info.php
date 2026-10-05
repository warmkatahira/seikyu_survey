<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 自分以外に作成できる人 is asked once for the whole invoice in 基本情報 instead of separately for
 * the 鑑 and the 明細. `dependency_option_id` (the 鑑 answer) becomes that answer and the 明細
 * column goes. An answer where either said 「いない（自分しか作れない）」 keeps saying so, as it
 * counted towards 「自分しか作れない」 before; otherwise the 鑑 answer wins, then the 明細 one.
 */
return new class extends Migration
{
    public function up(): void
    {
        $noneOnlyMeId = DB::table('choice_options')
            ->join('choice_categories', 'choice_categories.id', '=', 'choice_options.choice_category_id')
            ->where('choice_categories.key', 'dependency')
            ->where('choice_options.value', 'none_only_me')
            ->value('choice_options.id');

        if ($noneOnlyMeId !== null) {
            DB::table('survey_responses')->where('detail_dependency_option_id', $noneOnlyMeId)
                ->update(['dependency_option_id' => $noneOnlyMeId]);
        }

        DB::table('survey_responses')->whereNull('dependency_option_id')->whereNotNull('detail_dependency_option_id')
            ->update(['dependency_option_id' => DB::raw('detail_dependency_option_id')]);

        Schema::table('survey_responses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('detail_dependency_option_id');
        });
    }

    /**
     * Restores the 明細 column empty; the merged answer stays in `dependency_option_id`.
     */
    public function down(): void
    {
        Schema::table('survey_responses', function (Blueprint $table) {
            $table->foreignId('detail_dependency_option_id')->nullable()->after('detail_irregular_frequency_option_id')
                ->constrained('choice_options')->restrictOnDelete();
        });
    }
};
