<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The ticks of every multi-select question, one row per ticked option.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_response_choices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_response_id')->constrained()->cascadeOnDelete();
            $table->string('field', 50)->comment('SurveyResponse::FIELDS name of the multi-select ticked in');
            $table->foreignId('choice_option_id')->constrained()->restrictOnDelete();
            $table->string('other_text', 100)->nullable()
                ->comment('What その他 means, on the tick of the その他 option only');

            $table->unique(['survey_response_id', 'field', 'choice_option_id'], 'survey_response_choices_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_response_choices');
    }
};
