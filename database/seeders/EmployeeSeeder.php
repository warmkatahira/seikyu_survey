<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Office;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use RuntimeException;

class EmployeeSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Staff from 従業員情報_2026年10月07日10時29分09秒.csv, in its order. The CSV holds 拠点 and
     * name only, so codes are numbered here; the 拠点 is matched to an office by name.
     *
     * @var list<array{code: string, name: string, office: string}>
     */
    private const EMPLOYEES = [
        ['code' => 'EMP001', 'name' => '大泉 一弘', 'office' => '本社'],
        ['code' => 'EMP003', 'name' => '田村 拓海', 'office' => 'システム'],
        ['code' => 'EMP004', 'name' => '山村 祐一', 'office' => 'システム'],
        ['code' => 'EMP007', 'name' => '里本 佳隆', 'office' => '第1営業所'],
        ['code' => 'EMP008', 'name' => '永瀬 朱里', 'office' => '第1営業所'],
        ['code' => 'EMP009', 'name' => '鈴木 宏幸', 'office' => '第1営業所'],
        ['code' => 'EMP010', 'name' => '曽根田 裕也', 'office' => '第2営業所'],
        ['code' => 'EMP011', 'name' => '池田 仁', 'office' => '第2営業所'],
        ['code' => 'EMP012', 'name' => '吉田 健太郎', 'office' => '第2営業所'],
        ['code' => 'EMP013', 'name' => '渡部 洋', 'office' => '第2営業所'],
        ['code' => 'EMP014', 'name' => '水倉 陽介', 'office' => '第2営業所'],
        ['code' => 'EMP015', 'name' => 'サプコタ ドュルバ', 'office' => '第2営業所'],
        ['code' => 'EMP016', 'name' => '池田 博幸', 'office' => '第3営業所'],
        ['code' => 'EMP017', 'name' => '金子 太治', 'office' => '第3営業所'],
        ['code' => 'EMP018', 'name' => '杉本 利彦', 'office' => '第3営業所'],
        ['code' => 'EMP019', 'name' => '秋山 拓也', 'office' => '第3営業所'],
        ['code' => 'EMP020', 'name' => '内海 俊平', 'office' => 'ロジステーション'],
        ['code' => 'EMP021', 'name' => '五月女 健', 'office' => 'ロジステーション'],
        ['code' => 'EMP022', 'name' => '三国 純', 'office' => 'ロジステーション'],
        ['code' => 'EMP023', 'name' => '永瀬 健', 'office' => 'ロジポート'],
        ['code' => 'EMP024', 'name' => '掛川 純也', 'office' => 'ロジポート'],
        ['code' => 'EMP025', 'name' => '羽場 隆', 'office' => 'ロジポート'],
        ['code' => 'EMP026', 'name' => '高﨑 康幸', 'office' => 'ロジポート'],
        ['code' => 'EMP027', 'name' => '井上 朗', 'office' => 'ロジポート'],
        ['code' => 'EMP028', 'name' => '矢田 喜裕', 'office' => 'ロジポート'],
        ['code' => 'EMP029', 'name' => '静 剛盛', 'office' => 'ロジポート'],
        ['code' => 'EMP030', 'name' => '沖 直行', 'office' => '広島営業所'],
        ['code' => 'EMP031', 'name' => '南 伸一', 'office' => 'ロジコンタクト'],
        ['code' => 'EMP032', 'name' => '荻野 博之', 'office' => 'ロジコンタクト'],
        ['code' => 'EMP033', 'name' => '岩下 龍平', 'office' => 'ロジコンタクト'],
        ['code' => 'EMP034', 'name' => '飛田 祥吾', 'office' => 'ロジコンタクト'],
        ['code' => 'EMP035', 'name' => '江川 博', 'office' => 'ロジコンタクト'],
        ['code' => 'EMP036', 'name' => '森谷 元気', 'office' => 'IMP三郷'],
        ['code' => 'EMP037', 'name' => '瀬川 尚弘', 'office' => 'IMP三郷'],
        ['code' => 'EMP038', 'name' => '白井 滉大', 'office' => 'IMP三郷'],
        ['code' => 'EMP039', 'name' => '篠原 翔太', 'office' => 'IMP三郷'],
        ['code' => 'EMP040', 'name' => '豊田 達也', 'office' => 'IMP三郷'],
        ['code' => 'EMP041', 'name' => '星 孝志', 'office' => 'IMP三郷'],
        ['code' => 'EMP042', 'name' => '鈴木 莉希', 'office' => 'IMP三郷'],
        ['code' => 'EMP043', 'name' => '伊藤 良寛', 'office' => 'IMP三郷'],
        ['code' => 'EMP044', 'name' => '中西 亮太', 'office' => 'IMP三郷'],
        ['code' => 'EMP045', 'name' => '宮田 寿代', 'office' => 'IMP三郷'],
        ['code' => 'EMP046', 'name' => '堀池 美紀', 'office' => 'IMP三郷'],
        ['code' => 'EMP047', 'name' => '吉野 周一', 'office' => 'IMP三郷'],
        ['code' => 'EMP048', 'name' => '髙木 直樹', 'office' => 'IMP三郷'],
        ['code' => 'EMP049', 'name' => '藤田 憲一', 'office' => 'IMP三郷'],
        ['code' => 'EMP050', 'name' => '酒井 直美', 'office' => 'IMP三郷'],
        ['code' => 'EMP051', 'name' => '毛塚 勝則', 'office' => 'IMP三郷'],
        ['code' => 'EMP052', 'name' => '白取 広芸', 'office' => 'IMP三郷'],
        ['code' => 'EMP053', 'name' => '鈴木 大作', 'office' => 'IMP三郷'],
        ['code' => 'EMP054', 'name' => '石井 梨恵', 'office' => 'IMP三郷'],
    ];

    public function run(): void
    {
        $officeIds = Office::query()->pluck('id', 'name');

        foreach (self::EMPLOYEES as $index => $employee) {
            Employee::updateOrCreate(
                ['code' => $employee['code']],
                [
                    'name' => $employee['name'],
                    'office_id' => $officeIds[$employee['office']] ?? throw new RuntimeException("営業所「{$employee['office']}」がありません。"),
                    'sort_order' => ($index + 1) * 10,
                    'is_active' => true,
                ],
            );
        }
    }
}
