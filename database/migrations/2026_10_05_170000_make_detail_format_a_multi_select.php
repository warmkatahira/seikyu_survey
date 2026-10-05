<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 明細の形式 becomes a multi-select, as different 明細 of one invoice can come in different
 * formats. Each existing answer becomes a tick in survey_response_choices, its その他 text
 * moving onto that tick; the dropdown column and its その他 column go.
 */
return new class extends Migration
{
    private const FIELD = 'detail_format_option_ids';

    public function up(): void
    {
        DB::table('survey_responses')->whereNotNull('detail_format_option_id')
            ->get(['id', 'detail_format_option_id', 'detail_format_other'])
            ->each(fn (object $response) => DB::table('survey_response_choices')->insert([
                'survey_response_id' => $response->id,
                'field' => self::FIELD,
                'choice_option_id' => $response->detail_format_option_id,
                'other_text' => $response->detail_format_other,
            ]));

        Schema::table('survey_responses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('detail_format_option_id');
            $table->dropColumn('detail_format_other');
        });
    }

    /**
     * Restores the columns with each answer's first tick, in the dropdown's order.
     */
    public function down(): void
    {
        Schema::table('survey_responses', function (Blueprint $table) {
            $table->foreignId('detail_format_option_id')->nullable()->constrained('choice_options')->restrictOnDelete();
            $table->string('detail_format_other', 100)->nullable();
        });

        DB::table('survey_response_choices')
            ->join('choice_options', 'choice_options.id', '=', 'survey_response_choices.choice_option_id')
            ->where('survey_response_choices.field', self::FIELD)
            ->orderBy('choice_options.sort_order')
            ->get(['survey_response_choices.survey_response_id', 'survey_response_choices.choice_option_id', 'survey_response_choices.other_text'])
            ->unique('survey_response_id')
            ->each(fn (object $tick) => DB::table('survey_responses')->where('id', $tick->survey_response_id)->update([
                'detail_format_option_id' => $tick->choice_option_id,
                'detail_format_other' => $tick->other_text,
            ]));

        DB::table('survey_response_choices')->where('field', self::FIELD)->delete();
    }
};
