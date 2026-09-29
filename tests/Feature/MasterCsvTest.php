<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\Office;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class MasterCsvTest extends TestCase
{
    use RefreshDatabase;

    public function test_exporting_a_master_produces_a_file_excel_opens_without_mojibake(): void
    {
        $office = Office::factory()->create(['code' => 'OF001', 'name' => '第1営業所']);
        Employee::factory()->create(['code' => 'EMP001', 'name' => '山田 太郎', 'office_id' => $office->id]);

        $response = $this->actingAs($this->admin())->get(route('admin.masters.export', 'employees'));

        $response->assertOk();
        $contents = $response->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $contents, 'Excel needs the UTF-8 BOM to read Japanese correctly.');
        $this->assertStringContainsString('従業員コード,氏名,所属営業所コード,表示順,有効', $contents);
        // fputcsv quotes the value because the name contains a space.
        $this->assertStringContainsString('EMP001,"山田 太郎",OF001', $contents);
        $this->assertStringContainsString('有効', $contents);
    }

    public function test_the_customer_csv_has_no_office_column(): void
    {
        Customer::factory()->create(['code' => '1001', 'name' => '株式会社テスト', 'sort_order' => 10]);

        $contents = $this->actingAs($this->admin())
            ->get(route('admin.masters.export', 'customers'))
            ->streamedContent();

        $this->assertStringContainsString("顧客コード,顧客名,表示順,有効\n1001,株式会社テスト,10,有効", $contents);
    }

    public function test_importing_adds_new_rows_and_updates_existing_ones_by_code(): void
    {
        $office = Office::factory()->create(['code' => 'OF001']);
        Employee::factory()->create(['code' => 'EMP001', 'name' => '旧 名前', 'office_id' => null]);

        $this->actingAs($this->admin())
            ->post(route('admin.masters.import', 'employees'), [
                'file' => $this->csv([
                    '従業員コード,氏名,所属営業所コード,表示順,有効',
                    'EMP001,山田 太郎,OF001,10,有効',
                    'EMP002,佐藤 花子,,20,無効',
                ]),
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $updated = Employee::query()->firstWhere('code', 'EMP001');
        $this->assertSame('山田 太郎', $updated->name);
        $this->assertSame($office->id, $updated->office_id);

        $created = Employee::query()->firstWhere('code', 'EMP002');
        $this->assertSame('佐藤 花子', $created->name);
        $this->assertNull($created->office_id);
        $this->assertFalse($created->is_active);
    }

    public function test_a_shift_jis_file_saved_from_excel_is_imported(): void
    {
        $contents = mb_convert_encoding(
            "顧客コード,顧客名,表示順,有効\n9999,株式会社テスト,10,有効\n",
            'SJIS-win',
            'UTF-8',
        );

        $this->actingAs($this->admin())
            ->post(route('admin.masters.import', 'customers'), [
                'file' => UploadedFile::fake()->createWithContent('customers.csv', $contents),
            ])
            ->assertSessionHas('status');

        $this->assertSame('株式会社テスト', Customer::query()->firstWhere('code', '9999')->name);
    }

    public function test_one_bad_row_cancels_the_whole_import(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.masters.import', 'employees'), [
                'file' => $this->csv([
                    '従業員コード,氏名,所属営業所コード,表示順,有効',
                    'EMP001,山田 太郎,,10,有効',
                    'EMP002,佐藤 花子,NOPE,20,有効',
                ]),
            ])
            ->assertSessionHas('importErrors');

        $this->assertDatabaseCount('employees', 0);
    }

    public function test_a_code_repeated_within_the_file_is_reported(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.masters.import', 'employees'), [
                'file' => $this->csv([
                    '従業員コード,氏名,所属営業所コード,表示順,有効',
                    'EMP001,山田 太郎,,10,有効',
                    'EMP001,別 の人,,20,有効',
                ]),
            ])
            ->assertSessionHas('importErrors');

        $this->assertDatabaseCount('employees', 0);
    }

    public function test_a_file_with_the_wrong_headings_is_refused(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.masters.import', 'employees'), [
                'file' => $this->csv(['名前,メモ', '山田 太郎,なにか']),
            ])
            ->assertSessionHas('importErrors');

        $this->assertDatabaseCount('employees', 0);
    }

    public function test_the_shared_answer_account_cannot_import_a_master(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_RESPONDENT]))
            ->post(route('admin.masters.import', 'employees'), [
                'file' => $this->csv(['従業員コード,氏名,所属営業所コード,表示順,有効', 'EMP001,山田 太郎,,10,有効']),
            ])
            ->assertForbidden();
    }

    /**
     * @param  list<string>  $lines
     */
    private function csv(array $lines): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('master.csv', implode("\n", $lines)."\n");
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }
}
