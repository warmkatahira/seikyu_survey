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

    public function test_the_export_mirrors_the_column_order_of_the_original_excel_sheet(): void
    {
        $this->seed(ChoiceSeeder::class);

        $office = Office::factory()->create(['name' => '第1営業所']);
        $employee = Employee::factory()->create(['name' => '山田 太郎']);
        $customer = Customer::factory()->create(['code' => '9999', 'name' => '株式会社ＤＤＤＤＤＤ']);

        SurveyResponse::factory()->create([
            'employee_id' => $employee->id,
            'customer_id' => $customer->id,
            'office_id' => $office->id,
            'storage_fee_option_id' => $this->option('presence', 'yes'),
            'data_source_primary_option_id' => $this->option('data_source', 'excel_own'),
            'dependency_option_id' => $this->option('dependency', 'none_only_me'),
            'creation_minutes' => 45,
            'notes' => '保管日数の集計を手で数えている。',
        ]);

        $response = $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]))
            ->get(route('admin.responses.export'));

        $response->assertOk();
        $lines = explode("\n", trim($response->streamedContent()));

        $this->assertSame(
            "\xEF\xBB\xBF".'No.,顧客コード,顧客名,営業所・拠点,請求書の作成担当者,保管料,荷役料,運賃,作業・その他,'
                .'保管料の課金方式,締め日,別紙明細の有無,別紙明細の形式,実績データの出どころ（主）,実績データの出どころ（副）,'
                .'実績の記録タイミング,単価の根拠,前月ファイルのコピーで作成,1社あたりの作成時間（分）,'
                .'イレギュラー作業の発生頻度,自分以外に作成できる人,困っていること・特記事項,登録日時,更新日時',
            trim($lines[0]),
        );

        $this->assertStringContainsString('1,9999,株式会社ＤＤＤＤＤＤ,第1営業所,"山田 太郎",あり', $lines[1]);
        $this->assertStringContainsString('Excelの自作管理表', $lines[1]);
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
