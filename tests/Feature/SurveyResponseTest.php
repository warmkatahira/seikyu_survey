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
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use Tests\TestCase;

class SurveyResponseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ChoiceSeeder::class);
    }

    public function test_guests_are_sent_to_the_login_page(): void
    {
        $this->get(route('responses.index'))->assertRedirect(route('login'));
        $this->get(route('responses.create'))->assertRedirect(route('login'));
    }

    public function test_a_respondent_can_record_an_answer_for_a_customer(): void
    {
        $employee = Employee::factory()->create();
        $customer = Customer::factory()->create();
        $office = Office::factory()->create();

        $response = $this->actingAs($this->respondent())->post(route('responses.store'), [
            'employee_id' => $employee->id,
            'customer_id' => $customer->id,
            'office_id' => $office->id,
            'storage_fee_option_id' => $this->option('presence', 'yes'),
            'data_source_primary_option_id' => $this->option('data_source', 'excel_own'),
            'dependency_option_id' => $this->option('dependency', 'none_only_me'),
            'creation_minutes' => 45,
            'notes' => '保管日数の集計を手で数えている。',
        ]);

        $response->assertRedirect(route('responses.create'));

        $this->assertDatabaseHas('survey_responses', [
            'employee_id' => $employee->id,
            'customer_id' => $customer->id,
            'office_id' => $office->id,
            'creation_minutes' => 45,
            'notes' => '保管日数の集計を手で数えている。',
        ]);
    }

    public function test_the_answer_form_remembers_the_employee_who_answered_last(): void
    {
        $employee = Employee::factory()->create();
        $customer = Customer::factory()->create();

        $this->actingAs($this->respondent())
            ->post(route('responses.store'), [
                'employee_id' => $employee->id,
                'customer_id' => $customer->id,
                'office_id' => Office::factory()->create()->id,
            ])
            ->assertSessionHas('survey.last_employee_id', $employee->id);
    }

    public function test_an_option_belonging_to_another_dropdown_is_rejected(): void
    {
        $employee = Employee::factory()->create();
        $customer = Customer::factory()->create();

        $this->actingAs($this->respondent())
            ->post(route('responses.store'), [
                'employee_id' => $employee->id,
                'customer_id' => $customer->id,
                // A 自分以外に作成できる人 option offered where the 締め日 dropdown is expected.
                'closing_day_option_id' => $this->option('dependency', 'unknown'),
            ])
            ->assertSessionHasErrors('closing_day_option_id');

        $this->assertDatabaseCount('survey_responses', 0);
    }

    public function test_employee_customer_and_office_are_required(): void
    {
        $this->actingAs($this->respondent())
            ->post(route('responses.store'), [])
            ->assertSessionHasErrors(['employee_id', 'customer_id', 'office_id']);
    }

    public function test_anyone_logged_in_can_edit_and_delete_any_answer(): void
    {
        $answer = SurveyResponse::factory()->for(Office::factory())->create(['creation_minutes' => 30]);

        $this->actingAs($this->respondent())
            ->put(route('responses.update', $answer), [
                'employee_id' => $answer->employee_id,
                'customer_id' => $answer->customer_id,
                'office_id' => $answer->office_id,
                'creation_minutes' => 90,
            ])
            ->assertRedirect(route('responses.index'));

        $this->assertSame(90, $answer->refresh()->creation_minutes);

        $this->actingAs($this->respondent())
            ->delete(route('responses.destroy', $answer))
            ->assertRedirect(route('responses.index'));

        $this->assertDatabaseCount('survey_responses', 0);
    }

    public function test_the_answer_form_starts_with_the_respondents_own_office(): void
    {
        $office = Office::factory()->create();
        $employee = Employee::factory()->create(['office_id' => $office->id]);

        $this->actingAs($this->respondent())
            ->get(route('responses.create', ['employee_id' => $employee->id]))
            ->assertOk()
            ->assertViewHas('response', fn (SurveyResponse $response) => $response->office_id === $office->id);
    }

    public function test_the_employee_dropdown_is_grouped_by_office(): void
    {
        $second = Office::factory()->create(['name' => '第二営業所', 'sort_order' => 20]);
        $first = Office::factory()->create(['name' => '第一営業所', 'sort_order' => 10]);
        Employee::factory()->create(['name' => '未所属 花子', 'office_id' => null]);
        Employee::factory()->create(['name' => '第二 次郎', 'office_id' => $second->id]);
        Employee::factory()->create(['name' => '第一 太郎', 'office_id' => $first->id]);

        $this->actingAs($this->respondent())
            ->get(route('responses.create'))
            ->assertOk()
            ->assertSeeInOrder([
                '<optgroup label="第一営業所">', '第一 太郎',
                '<optgroup label="第二営業所">', '第二 次郎',
                '<optgroup label="営業所未設定">', '未所属 花子',
            ], false);
    }

    public function test_the_list_can_be_filtered_by_customer(): void
    {
        $wanted = SurveyResponse::factory()
            ->for(Customer::factory()->state(['code' => '1111', 'name' => '株式会社ＡＡＡＡ']))
            ->create();
        SurveyResponse::factory()
            ->for(Customer::factory()->state(['code' => '2222', 'name' => '株式会社ＢＢＢＢ']))
            ->create();

        $this->actingAs($this->respondent())
            ->get(route('responses.index', ['customer_id' => $wanted->customer_id]))
            ->assertOk()
            ->assertViewHas('responses', fn ($responses) => $responses->pluck('id')->all() === [$wanted->id]);
    }

    public function test_the_list_shows_when_each_answer_was_given_and_last_updated(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 1)->setTime(9, 5));
        $answer = SurveyResponse::factory()->create();

        $this->travelTo(now()->setDate(2026, 10, 3)->setTime(14, 30));
        $answer->update(['creation_minutes' => 60]);

        $this->actingAs($this->respondent())
            ->get(route('responses.index'))
            ->assertOk()
            ->assertSeeInOrder(['回答日時', '更新日時', '2026/10/01 09:05', '2026/10/03 14:30'])
            ->assertDontSee('自分以外に作成できる人');
    }

    public function test_the_list_downloads_as_an_excel_workbook_limited_to_the_current_filter(): void
    {
        $wanted = SurveyResponse::factory()
            ->for(Customer::factory()->state(['code' => '1111', 'name' => '株式会社ＡＡＡＡ']))
            ->create(['creation_minutes' => 45]);
        SurveyResponse::factory()
            ->for(Customer::factory()->state(['code' => '2222', 'name' => '株式会社ＢＢＢＢ']))
            ->create();

        $response = $this->actingAs($this->respondent())
            ->get(route('responses.export', ['customer_id' => $wanted->customer_id]));

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->assertDownload();

        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($path, $response->streamedContent());

        $reader = new XlsxReader;
        $reader->open($path);
        $rows = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            $this->assertSame('回答一覧', $sheet->getName());
            foreach ($sheet->getRowIterator() as $row) {
                $rows[] = $row->toArray();
            }
        }
        $reader->close();
        unlink($path);

        $this->assertCount(2, $rows, 'The heading row plus only the filtered answer.');
        $this->assertSame(['No.', '顧客コード', '顧客名'], array_slice($rows[0], 0, 3));
        $this->assertSame([1, '1111', '株式会社ＡＡＡＡ'], array_slice($rows[1], 0, 3));
        $this->assertContains(45, $rows[1], '作成時間 stays a number Excel can sum.');
    }

    public function test_an_answer_can_be_read_without_opening_the_edit_form(): void
    {
        $answer = SurveyResponse::factory()
            ->for(Customer::factory()->state(['code' => '1111', 'name' => '株式会社ＡＡＡＡ']))
            ->create([
                'closing_day_option_id' => $this->option('closing_day', 'month_end'),
                'creation_minutes' => 45,
                'notes' => "月末に手作業で集計している。\n繁忙期は2日かかる。",
            ]);

        $this->actingAs($this->respondent())
            ->get(route('responses.index'))
            ->assertSee('data-href="'.route('responses.show', $answer).'"', false);

        $this->actingAs($this->respondent())
            ->get(route('responses.show', $answer))
            ->assertOk()
            ->assertSee('株式会社ＡＡＡＡ')
            ->assertSeeInOrder(['締め日', '月末', '1社あたりの作成時間（分）', '45 分', '繁忙期は2日かかる。'])
            ->assertSee('未回答')
            ->assertDontSee('name="creation_minutes"', false);
    }

    public function test_one_customer_invoiced_separately_is_told_apart_by_its_billing_category(): void
    {
        $customer = Customer::factory()->create(['name' => '株式会社ＡＡＡＡ']);

        $this->actingAs($this->respondent())
            ->post(route('responses.store'), [
                'employee_id' => Employee::factory()->create()->id,
                'customer_id' => $customer->id,
                'billing_category' => '通販',
                'office_id' => Office::factory()->create()->id,
            ])
            ->assertSessionHas('status', fn (string $status) => str_contains($status, '株式会社ＡＡＡＡ（通販）'));

        $answer = SurveyResponse::query()->sole();
        $this->assertSame('通販', $answer->billing_category);

        $this->actingAs($this->respondent())
            ->get(route('responses.show', $answer))
            ->assertSee('株式会社ＡＡＡＡ（通販）')
            ->assertSeeInOrder(['作成区分', '通販']);
    }

    public function test_an_answer_keeps_showing_an_option_that_was_later_deactivated(): void
    {
        $deactivated = ChoiceOption::query()->find($this->option('closing_day', 'month_end'));
        $answer = SurveyResponse::factory()->create(['closing_day_option_id' => $deactivated->id]);

        $deactivated->update(['is_active' => false]);

        $this->actingAs($this->respondent())
            ->get(route('responses.edit', $answer))
            ->assertOk()
            ->assertSee('月末（無効）');
    }

    private function respondent(): User
    {
        return User::factory()->create(['role' => User::ROLE_RESPONDENT]);
    }

    private function option(string $categoryKey, string $value): int
    {
        return ChoiceOption::query()
            ->whereRelation('category', 'key', $categoryKey)
            ->where('value', $value)
            ->value('id');
    }
}
