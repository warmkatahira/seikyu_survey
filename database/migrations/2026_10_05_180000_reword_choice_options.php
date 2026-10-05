<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Rewords two options: 実績データの出どころ 運送会社の請求データ・送り状データ becomes
 * 請求データ・請求書, and 明細の形式 手書き・紙をスキャン becomes 手書き.
 */
return new class extends Migration
{
    /**
     * [category key, option value, old label, new label]
     *
     * @var list<array{string, string, string, string}>
     */
    private const LABELS = [
        ['data_source', 'carrier_invoice', '運送会社の請求データ・送り状データ', '請求データ・請求書'],
        ['detail_format', 'handwritten_scan', '手書き・紙をスキャン', '手書き'],
    ];

    public function up(): void
    {
        foreach (self::LABELS as [$category, $value, , $label]) {
            $this->relabel($category, $value, $label);
        }
    }

    public function down(): void
    {
        foreach (self::LABELS as [$category, $value, $label]) {
            $this->relabel($category, $value, $label);
        }
    }

    private function relabel(string $categoryKey, string $value, string $label): void
    {
        $categoryId = DB::table('choice_categories')->where('key', $categoryKey)->value('id');

        DB::table('choice_options')->where('choice_category_id', $categoryId)->where('value', $value)
            ->update(['label' => $label, 'updated_at' => now()]);
    }
};
