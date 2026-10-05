<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The 締め日 question is dropped from the survey, together with its dropdown master.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('survey_responses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('closing_day_option_id');
        });

        $categoryId = DB::table('choice_categories')->where('key', 'closing_day')->value('id');

        if ($categoryId !== null) {
            DB::table('choice_options')->where('choice_category_id', $categoryId)->delete();
            DB::table('choice_categories')->where('id', $categoryId)->delete();
        }
    }

    /**
     * Restores the empty column only; rerun ChoiceSeeder from before this change for the options.
     */
    public function down(): void
    {
        Schema::table('survey_responses', function (Blueprint $table) {
            $table->foreignId('closing_day_option_id')->nullable()->after('office_id')
                ->constrained('choice_options')->restrictOnDelete();
        });
    }
};
