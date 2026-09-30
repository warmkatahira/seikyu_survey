<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The 保管料の課金方式 question is dropped from the survey, together with its dropdown master.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('survey_responses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('storage_billing_method_option_id');
        });

        $categoryId = DB::table('choice_categories')->where('key', 'storage_billing_method')->value('id');

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
            $table->foreignId('storage_billing_method_option_id')->nullable()->after('other_work_option_id')
                ->constrained('choice_options')->restrictOnDelete();
        });
    }
};
