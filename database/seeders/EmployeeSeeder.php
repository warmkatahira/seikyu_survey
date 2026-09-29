<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Office;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Sample staff so the answer form has a usable 作成担当者 dropdown before the real
     * employee master is imported from CSV. Replace these with the real list via
     * /admin/employees once it is available.
     *
     * @var list<array{code: string, name: string, office: string}>
     */
    private const EMPLOYEES = [
        ['code' => 'EMP001', 'name' => '山田 太郎', 'office' => 'OF002'],
        ['code' => 'EMP002', 'name' => '佐藤 花子', 'office' => 'OF001'],
        ['code' => 'EMP003', 'name' => '鈴木 一郎', 'office' => 'OF003'],
        ['code' => 'EMP004', 'name' => '高橋 美咲', 'office' => 'OF002'],
        ['code' => 'EMP005', 'name' => '田中 健太', 'office' => 'OF004'],
        ['code' => 'EMP006', 'name' => '伊藤 由美', 'office' => 'OF005'],
        ['code' => 'EMP007', 'name' => '渡辺 翔', 'office' => 'OF006'],
        ['code' => 'EMP008', 'name' => '中村 彩', 'office' => 'OF001'],
        ['code' => 'EMP009', 'name' => '小林 大輔', 'office' => 'OF009'],
        ['code' => 'EMP010', 'name' => '加藤 直樹', 'office' => 'OF011'],
    ];

    public function run(): void
    {
        $officeIds = Office::query()->pluck('id', 'code');

        foreach (self::EMPLOYEES as $index => $employee) {
            Employee::updateOrCreate(
                ['code' => $employee['code']],
                [
                    'name' => $employee['name'],
                    'office_id' => $officeIds->get($employee['office']),
                    'sort_order' => ($index + 1) * 10,
                    'is_active' => true,
                ],
            );
        }
    }
}
