<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The form is split into 鑑について and 明細について, each asking for its own 実績データの取得方法,
 * 単価・作成方法 and 工数・属人度. The existing answer columns become the 鑑 answers; the 明細
 * gets its own set alongside them.
 *
 * Which items an invoice carries is now ticked from one 請求項目 list (保管・荷役・運賃…), once
 * for the 鑑 (鑑に載せている項目, replacing the four あり／なし dropdowns) and once for the
 * 明細 (作成している明細, replacing 別紙明細の有無). Both are kept in one pivot table told apart
 * by `part`. The dropdown masters that are no longer used go and the new one is added here,
 * keeping any deployment self-contained without re-running ChoiceSeeder over administrators'
 * edits.
 */
return new class extends Migration
{
    /**
     * The 明細 counterpart of each 鑑 column, in form order.
     *
     * @var list<string>
     */
    private const DETAIL_CHOICE_COLUMNS = [
        'detail_data_source_primary_option_id',
        'detail_data_source_secondary_option_id',
        'detail_record_timing_option_id',
        'detail_price_basis_option_id',
        'detail_copy_previous_month_option_id',
        'detail_irregular_frequency_option_id',
        'detail_dependency_option_id',
    ];

    /** @var array<string, string> */
    private const BILLING_ITEMS = [
        'storage' => '保管',
        'handling' => '荷役',
        'freight' => '運賃',
        'work' => '作業',
        'other' => 'その他',
    ];

    /**
     * The old あり／なし columns of 鑑に載せている項目, and the 請求項目 each 「あり」 becomes.
     *
     * @var array<string, string>
     */
    private const PRESENCE_COLUMNS = [
        'storage_fee_option_id' => 'storage',
        'handling_fee_option_id' => 'handling',
        'freight_fee_option_id' => 'freight',
        'other_work_option_id' => 'work',
    ];

    public function up(): void
    {
        Schema::table('survey_responses', function (Blueprint $table) {
            $after = 'digitization_request_option_id';

            foreach (self::DETAIL_CHOICE_COLUMNS as $column) {
                $table->foreignId($column)->nullable()->after($after)
                    ->constrained('choice_options')->restrictOnDelete();
                $after = $column;
            }

            $table->unsignedSmallInteger('detail_creation_minutes')->nullable()->after('creation_minutes');
        });

        Schema::create('survey_response_billing_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_response_id')->constrained()->cascadeOnDelete();
            $table->string('part', 10)->comment('cover = 鑑に載せている項目, detail = 作成している明細');
            $table->foreignId('choice_option_id')->constrained()->restrictOnDelete();

            $table->unique(['survey_response_id', 'part', 'choice_option_id'], 'survey_response_billing_items_unique');
        });

        $itemIds = $this->createBillingItemCategory();
        $this->carryOverPresenceAnswers($itemIds);

        Schema::table('survey_responses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('detail_presence_option_id');

            foreach (array_keys(self::PRESENCE_COLUMNS) as $column) {
                $table->dropConstrainedForeignId($column);
            }
        });

        $this->deleteCategory('detail_presence');
        $this->deleteCategory('presence');
    }

    /**
     * Restores the emptied columns only; rerun ChoiceSeeder from before this change for the
     * 有無 and 別紙明細の有無 options. Ticked 請求項目 are not carried back.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_response_billing_items');
        $this->deleteCategory('billing_item');

        Schema::table('survey_responses', function (Blueprint $table) {
            $table->dropColumn('detail_creation_minutes');

            foreach (self::DETAIL_CHOICE_COLUMNS as $column) {
                $table->dropConstrainedForeignId($column);
            }

            $after = 'office_id';

            foreach (array_keys(self::PRESENCE_COLUMNS) as $column) {
                $table->foreignId($column)->nullable()->after($after)
                    ->constrained('choice_options')->restrictOnDelete();
                $after = $column;
            }

            $table->foreignId('detail_presence_option_id')->nullable()->after('closing_day_option_id')
                ->constrained('choice_options')->restrictOnDelete();
        });
    }

    /**
     * @return array<string, int> option id by value
     */
    private function createBillingItemCategory(): array
    {
        $categoryId = DB::table('choice_categories')->where('key', 'billing_item')->value('id');

        if ($categoryId === null) {
            $now = now();

            $categoryId = DB::table('choice_categories')->insertGetId([
                'key' => 'billing_item',
                'name' => '請求項目',
                'description' => '「鑑に載せている項目」と「作成している明細」で使用します（複数選択）。',
                'sort_order' => 10,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $sort = 0;

            foreach (self::BILLING_ITEMS as $value => $label) {
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

        return DB::table('choice_options')->where('choice_category_id', $categoryId)->pluck('id', 'value')->all();
    }

    /**
     * Each 「あり」 answered in the old dropdowns becomes a ticked 鑑に載せている項目.
     *
     * @param  array<string, int>  $itemIds
     */
    private function carryOverPresenceAnswers(array $itemIds): void
    {
        $yesId = DB::table('choice_options')
            ->join('choice_categories', 'choice_categories.id', '=', 'choice_options.choice_category_id')
            ->where('choice_categories.key', 'presence')
            ->where('choice_options.value', 'yes')
            ->value('choice_options.id');

        if ($yesId === null) {
            return;
        }

        foreach (self::PRESENCE_COLUMNS as $column => $item) {
            DB::table('survey_responses')->where($column, $yesId)->pluck('id')
                ->each(fn (int $responseId) => DB::table('survey_response_billing_items')->insert([
                    'survey_response_id' => $responseId,
                    'part' => 'cover',
                    'choice_option_id' => $itemIds[$item],
                ]));
        }
    }

    private function deleteCategory(string $key): void
    {
        $categoryId = DB::table('choice_categories')->where('key', $key)->value('id');

        if ($categoryId !== null) {
            DB::table('choice_options')->where('choice_category_id', $categoryId)->delete();
            DB::table('choice_categories')->where('id', $categoryId)->delete();
        }
    }
};
