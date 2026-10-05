<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Sample customers so the answer form has a usable 顧客 dropdown before the real
     * customer master is imported from CSV. Names are taken from 売上一覧表_2026年09月.xlsx,
     * but the office grouping is arbitrary and only for readability (customers carry no
     * office). Replace these via /admin/customers once the real list is available.
     *
     * @var list<array{code: string, name: string}>
     */
    private const CUSTOMERS = [
        // 本社
        ['code' => 'CUS001', 'name' => '㈱アイネクスト'],
        // 第1営業所
        ['code' => 'CUS002', 'name' => '朝日食品㈱'],
        ['code' => 'CUS003', 'name' => '㈱イン・フィールド'],
        // 第2営業所
        ['code' => 'CUS004', 'name' => 'MXロジスティクス'],
        // 第3営業所
        ['code' => 'CUS005', 'name' => '㈱エンマバイシクルワークス'],
        ['code' => 'CUS006', 'name' => '㈱オルガニコ'],
        // ロジポート
        ['code' => 'CUS007', 'name' => '㈱キャラバン'],
        // ロジステーション
        ['code' => 'CUS008', 'name' => '㈱グッドフェイス'],
        ['code' => 'CUS009', 'name' => 'コムテック㈱'],
        // ロジコンタクト
        ['code' => 'CUS010', 'name' => '㈱ジャストドゥイット'],
        // 倉庫
        ['code' => 'CUS011', 'name' => 'スタンバイ㈱'],
        ['code' => 'CUS012', 'name' => '大洋製薬㈱'],
        // 広島
        ['code' => 'CUS013', 'name' => '中央化学産業㈱'],
        // IMP三郷
        ['code' => 'CUS014', 'name' => '㈱ティーエス企画'],
        ['code' => 'CUS015', 'name' => '㈱日本システムズ'],
        // システム
        ['code' => 'CUS016', 'name' => '㈱フクヤ'],
    ];

    public function run(): void
    {
        foreach (self::CUSTOMERS as $index => $customer) {
            Customer::updateOrCreate(
                ['code' => $customer['code']],
                ['name' => $customer['name'], 'sort_order' => ($index + 1) * 10, 'is_active' => true],
            );
        }
    }
}
