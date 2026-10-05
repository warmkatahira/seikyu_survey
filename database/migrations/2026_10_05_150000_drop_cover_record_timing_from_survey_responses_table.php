<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 鑑について no longer asks 実績入力タイミング; the 明細 question (実績の記録タイミング) stays.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('survey_responses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('record_timing_option_id');
            $table->dropColumn('record_timing_other');
        });
    }

    /**
     * Restores the empty columns only.
     */
    public function down(): void
    {
        Schema::table('survey_responses', function (Blueprint $table) {
            $table->foreignId('record_timing_option_id')->nullable()->constrained('choice_options')->restrictOnDelete();
            $table->string('record_timing_other', 100)->nullable();
        });
    }
};
