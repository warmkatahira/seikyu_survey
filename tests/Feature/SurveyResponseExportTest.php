<?php

namespace Tests\Feature;

use App\Models\ChoiceOption;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Office;
use App\Models\SurveyResponse;
use App\Models\User;
use Database\Seeders\ChoiceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurveyResponseExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_export_has_one_column_per_question_in_form_order(): void
    {
        $this->seed(ChoiceSeeder::class);

        $office = Office::factory()->create(['name' => '第1営業所']);
        $employee = Employee::factory()->create(['name' => '山田 太郎']);
        $customer = Customer::factory()->create(['code' => '9999', 'name' => '株式会社ＤＤＤＤＤＤ']);

        $answer = SurveyResponse::factory()->create([
            'employee_id' => $employee->id,
            'customer_id' => $customer->id,
            'office_id' => $office->id,
            'invoice_composition_option_id' => $this->option('invoice_composition', 'cover_and_detail'),
            'customer_check_option_id' => $this->option('yes_no', 'yes'),
            'detail_mailing_option_id' => $this->option('detail_mailing', 'partly'),
            'dependency_option_id' => $this->option('dependency', 'none_only_me'),
            'creation_minutes' => 45,
            'detail_creation_minutes' => 120,
            'notes' => '保管日数の集計を手で数えている。',
        ]);
        $answer->syncChoices([
            'detail_item_ids' => [$this->option('billing_item', 'freight'), $this->option('billing_item', 'storage')],
            'data_source_option_ids' => [$this->option('data_source', 'excel_own'), $this->option('data_source', 'wms')],
            'tool_option_ids' => [$this->option('tool', 'yamato_freight'), $this->option('tool', 'other')],
            'tool_option_ids_other' => '自作マクロ',
            'detail_format_option_ids' => [$this->option('detail_format', 'other')],
            'detail_format_option_ids_other' => 'Googleスプレッドシート',
            'detail_unmailed_option_ids' => [$this->option('detail_unmailed', 'email'), $this->option('detail_unmailed', 'fax_or_hand')],
        ]);

        $response = $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]))
            ->get(route('admin.responses.export'));

        $response->assertOk();
        $lines = explode("\n", trim($response->streamedContent()));

        $this->assertSame(
            "\xEF\xBB\xBF".'No.,顧客コード,顧客名,作成区分,営業所・拠点,請求書の作成担当者,請求書の構成,送付前のお客様確認,自分以外に作成できる人,'
                .'鑑：実績データの出どころ,'
                .'鑑：作成時間（分）,'
                .'明細：作成している明細,明細：明細の形式,明細：明細の郵送,明細：郵送していない明細の扱い,'
                .'明細：実績データの出どころ,明細：実績の記録タイミング,'
                .'明細：作成時間（分）,'
                .'現状使用しているツールについて,'
                .'請求書の電子化の要望,困っていること・特記事項,登録日時,更新日時',
            trim($lines[0]),
        );

        $this->assertStringContainsString('1,9999,株式会社ＤＤＤＤＤＤ,,第1営業所,"山田 太郎",鑑と明細,はい,いない（自分しか作れない）,出荷システム（WMS）、Excelの自作管理表,', $lines[1]);
        $this->assertStringContainsString(',保管、運賃,その他（Googleスプレッドシート）,一部郵送している,メールで送付、FAX・手渡し,', $lines[1]);
        $this->assertStringContainsString(',45,', $lines[1]);
        $this->assertStringContainsString(',120,運賃算出ツール（ヤマト運輸）、その他（自作マクロ）,', $lines[1]);
        $this->assertStringContainsString(',120,', $lines[1]);
        $this->assertStringContainsString(',出荷システム（WMS）、Excelの自作管理表,', $lines[1]);
        $this->assertStringContainsString('いない（自分しか作れない）', $lines[1]);
    }

    private function option(string $categoryKey, string $value): int
    {
        return ChoiceOption::query()
            ->whereRelation('category', 'key', $categoryKey)
            ->where('value', $value)
            ->value('id');
    }
}
