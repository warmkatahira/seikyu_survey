<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 実績データの出どころ is no longer asked as （主） and （副） dropdowns but as one multi-select,
 * for 鑑 and 明細 alike. The pivot table that held only the ticked 請求項目 now holds the ticks
 * of every multi-select, told apart by `field` (the FIELDS name) instead of the old `part`
 * (cover / detail). Existing 主 and 副 answers become ticks.
 */
return new class extends Migration
{
    /**
     * The new multi-select of each section, and the 主・副 columns carried into it.
     *
     * @var array<string, list<string>>
     */
    private const DATA_SOURCE_COLUMNS = [
        'data_source_option_ids' => ['data_source_primary_option_id', 'data_source_secondary_option_id'],
        'detail_data_source_option_ids' => ['detail_data_source_primary_option_id', 'detail_data_source_secondary_option_id'],
    ];

    /**
     * The old `part` values and the field each one meant.
     *
     * @var array<string, string>
     */
    private const PARTS = [
        'cover' => 'cover_item_ids',
        'detail' => 'detail_item_ids',
    ];

    public function up(): void
    {
        Schema::rename('survey_response_billing_items', 'survey_response_choices');

        // The new unique index goes in before the old one is dropped, so the foreign key on
        // survey_response_id is never left without an index.
        Schema::table('survey_response_choices', function (Blueprint $table) {
            $table->renameColumn('part', 'field');
        });

        Schema::table('survey_response_choices', function (Blueprint $table) {
            $table->string('field', 50)->comment('SurveyResponse::FIELDS name of the multi-select ticked in')->change();
            $table->unique(['survey_response_id', 'field', 'choice_option_id'], 'survey_response_choices_unique');
            $table->dropUnique('survey_response_billing_items_unique');
        });

        foreach (self::PARTS as $part => $field) {
            DB::table('survey_response_choices')->where('field', $part)->update(['field' => $field]);
        }

        foreach (self::DATA_SOURCE_COLUMNS as $field => $columns) {
            foreach (DB::table('survey_responses')->get(['id', ...$columns]) as $response) {
                collect($columns)->map(fn (string $column) => $response->{$column})->filter()->unique()
                    ->each(fn (int $optionId) => DB::table('survey_response_choices')->insert([
                        'survey_response_id' => $response->id,
                        'field' => $field,
                        'choice_option_id' => $optionId,
                    ]));
            }
        }

        DB::table('choice_categories')->where('key', 'data_source')->update([
            'description' => '請求金額（数量）の根拠となるデータの入手元。鑑・明細それぞれで使用します（複数選択）。',
        ]);

        Schema::table('survey_responses', function (Blueprint $table) {
            foreach (self::DATA_SOURCE_COLUMNS as $columns) {
                foreach ($columns as $column) {
                    $table->dropConstrainedForeignId($column);
                }
            }
        });
    }

    /**
     * Restores the 主・副 columns empty; ticked 実績データの出どころ are dropped, not carried back.
     */
    public function down(): void
    {
        Schema::table('survey_responses', function (Blueprint $table) {
            $table->foreignId('data_source_primary_option_id')->nullable()->after('office_id')
                ->constrained('choice_options')->restrictOnDelete();
            $table->foreignId('data_source_secondary_option_id')->nullable()->after('data_source_primary_option_id')
                ->constrained('choice_options')->restrictOnDelete();
            $table->foreignId('detail_data_source_primary_option_id')->nullable()->after('digitization_request_option_id')
                ->constrained('choice_options')->restrictOnDelete();
            $table->foreignId('detail_data_source_secondary_option_id')->nullable()->after('detail_data_source_primary_option_id')
                ->constrained('choice_options')->restrictOnDelete();
        });

        DB::table('survey_response_choices')->whereIn('field', array_keys(self::DATA_SOURCE_COLUMNS))->delete();

        foreach (self::PARTS as $part => $field) {
            DB::table('survey_response_choices')->where('field', $field)->update(['field' => $part]);
        }

        Schema::table('survey_response_choices', function (Blueprint $table) {
            $table->string('field', 10)->comment('cover = 鑑に載せている項目, detail = 作成している明細')->change();
        });

        Schema::table('survey_response_choices', function (Blueprint $table) {
            $table->renameColumn('field', 'part');
        });

        Schema::table('survey_response_choices', function (Blueprint $table) {
            $table->unique(['survey_response_id', 'part', 'choice_option_id'], 'survey_response_billing_items_unique');
            $table->dropUnique('survey_response_choices_unique');
        });

        Schema::rename('survey_response_choices', 'survey_response_billing_items');
    }
};
