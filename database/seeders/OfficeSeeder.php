<?php

namespace Database\Seeders;

use App\Models\Office;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OfficeSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * The 拠点 list from the original Excel 選択肢マスタ sheet (倉庫 and 広島 renamed to
     * 倉庫管理 and 広島営業所 since).
     *
     * @var list<string>
     */
    private const OFFICES = [
        '本社',
        '第1営業所',
        '第2営業所',
        '第3営業所',
        'ロジポート',
        'ロジステーション',
        'ロジコンタクト',
        '倉庫管理',
        '広島営業所',
        'IMP三郷',
        'システム',
    ];

    public function run(): void
    {
        foreach (self::OFFICES as $index => $name) {
            Office::updateOrCreate(
                ['code' => sprintf('OF%03d', $index + 1)],
                ['name' => $name, 'sort_order' => ($index + 1) * 10, 'is_active' => true],
            );
        }
    }
}
