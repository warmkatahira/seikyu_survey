<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 基本情報 asks whether the invoice is the 鑑 only or the 鑑 with 明細; 鑑のみ skips 明細について.
 * The dropdown master is added here, keeping any deployment self-contained without re-running
 * ChoiceSeeder over administrators' edits.
 */
return new class extends Migration
{
    /** @var array<string, string> */
    private const OPTIONS = [
        'cover_only' => '鑑のみ',
        'cover_and_detail' => '鑑と明細',
    ];

    public function up(): void
    {
        if (! DB::table('choice_categories')->where('key', 'invoice_composition')->exists()) {
            $now = now();

            $categoryId = DB::table('choice_categories')->insertGetId([
                'key' => 'invoice_composition',
                'name' => '請求書の構成',
                'description' => '「鑑のみ」を選ぶと「明細について」は回答不要になります。',
                'sort_order' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $sort = 0;

            foreach (self::OPTIONS as $value => $label) {
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

        Schema::table('survey_responses', function (Blueprint $table) {
            $table->foreignId('invoice_composition_option_id')->nullable()->after('office_id')
                ->constrained('choice_options')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('survey_responses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('invoice_composition_option_id');
        });

        $categoryId = DB::table('choice_categories')->where('key', 'invoice_composition')->value('id');

        if ($categoryId !== null) {
            DB::table('choice_options')->where('choice_category_id', $categoryId)->delete();
            DB::table('choice_categories')->where('id', $categoryId)->delete();
        }
    }
};
