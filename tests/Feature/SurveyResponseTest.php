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
            'cover_item_ids' => [$this->option('billing_item', 'storage')],
            'data_source_option_ids' => [$this->option('data_source', 'excel_own'), $this->option('data_source', 'wms')],
            'dependency_option_id' => $this->option('dependency', 'none_only_me'),
            'digitization_request_option_id' => $this->option('yes_no', 'yes'),
            'creation_minutes' => 45,
            'notes' => '保管日数の集計を手で数えている。',
        ]);

        $response->assertRedirect(route('responses.create'));

        $this->assertDatabaseHas('survey_responses', [
            'employee_id' => $employee->id,
            'customer_id' => $customer->id,
            'office_id' => $office->id,
            'digitization_request_option_id' => $this->option('yes_no', 'yes'),
            'creation_minutes' => 45,
            'notes' => '保管日数の集計を手で数えている。',
        ]);
        $this->assertSame(
            [$this->option('data_source', 'wms'), $this->option('data_source', 'excel_own')],
            SurveyResponse::query()->sole()->data_source_option_ids,
        );
    }

    public function test_the_answer_form_sent_in_the_background_is_told_where_to_go_next(): void
    {
        $customer = Customer::factory()->create(['name' => '株式会社ＡＡＡＡ']);

        $this->actingAs($this->respondent())
            ->postJson(route('responses.store'), [
                'employee_id' => Employee::factory()->create()->id,
                'customer_id' => $customer->id,
                'office_id' => Office::factory()->create()->id,
            ])
            ->assertOk()
            ->assertExactJson(['redirect' => route('responses.create')])
            ->assertSessionHas('status', fn (string $status) => str_contains($status, '株式会社ＡＡＡＡ'));

        $this->assertDatabaseCount('survey_responses', 1);
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
                // A 自分以外に作成できる人 option offered where the 実績の記録タイミング dropdown is expected.
                'record_timing_option_id' => $this->option('dependency', 'unknown'),
            ])
            ->assertSessionHasErrors('record_timing_option_id');

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
                'record_timing_option_id' => $this->option('record_timing', 'daily'),
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
            ->assertSeeInOrder(['鑑について', '実績入力タイミング', '毎日その都度入力している', '作成時間（分）', '45 分', '明細について', '繁忙期は2日かかる。'])
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
        $deactivated = ChoiceOption::query()->find($this->option('record_timing', 'daily'));
        $answer = SurveyResponse::factory()->create(['record_timing_option_id' => $deactivated->id]);

        $deactivated->update(['is_active' => false]);

        $this->actingAs($this->respondent())
            ->get(route('responses.edit', $answer))
            ->assertOk()
            ->assertSee('毎日その都度入力している（無効）');
    }

    public function test_the_form_asks_about_the_cover_and_the_detail_separately(): void
    {
        $this->actingAs($this->respondent())
            ->get(route('responses.create'))
            ->assertOk()
            ->assertSeeInOrder([
                '基本情報',
                '鑑について', '鑑に載せている項目', '保管', '荷役', '運賃',
                '実績データの取得方法', '単価・作成方法', '工数・属人度',
                '明細について', '作成している明細', '保管', '荷役', '運賃',
                '実績データの取得方法', '単価・作成方法', '工数・属人度',
                '顧客からの要望', '自由記述',
            ])
            ->assertSee('name="cover_item_ids[]"', false)
            ->assertSee('name="detail_item_ids[]"', false)
            ->assertDontSee('name="storage_fee_option_id"', false)
            ->assertSee('name="detail_creation_minutes"', false)
            ->assertDontSee('別紙明細の有無');
    }

    public function test_billing_items_are_ticked_separately_for_the_cover_and_the_detail(): void
    {
        $storage = $this->option('billing_item', 'storage');
        $handling = $this->option('billing_item', 'handling');
        $freight = $this->option('billing_item', 'freight');
        $base = [
            'employee_id' => Employee::factory()->create()->id,
            'customer_id' => Customer::factory()->create()->id,
            'office_id' => Office::factory()->create()->id,
        ];

        $this->actingAs($this->respondent())
            ->post(route('responses.store'), $base + [
                'cover_item_ids' => [$storage, $handling, $freight],
                'detail_item_ids' => [$freight, $storage],
                'creation_minutes' => 15,
                'detail_creation_minutes' => 60,
                'dependency_option_id' => $this->option('dependency', 'two_or_more'),
                'detail_dependency_option_id' => $this->option('dependency', 'none_only_me'),
            ])
            ->assertSessionHasNoErrors();

        $answer = SurveyResponse::query()->sole();
        $this->assertSame([$storage, $handling, $freight], $answer->cover_item_ids);
        $this->assertSame([$storage, $freight], $answer->detail_item_ids);
        $this->assertSame(75, $answer->totalMinutes());

        $this->actingAs($this->respondent())
            ->get(route('responses.show', $answer))
            ->assertSeeInOrder(['鑑に載せている項目', '保管、荷役、運賃', '作成している明細', '保管、運賃']);

        $this->actingAs($this->respondent())
            ->get(route('responses.index'))
            ->assertViewHas('totals', ['answered' => 1, 'minutes' => 75, 'sole_owner' => 1]);

        $this->actingAs($this->respondent())
            ->put(route('responses.update', $answer), $base + ['cover_item_ids' => [$handling]])
            ->assertSessionHasNoErrors();

        $answer->refresh();
        $this->assertSame([$handling], $answer->cover_item_ids);
        $this->assertSame([], $answer->detail_item_ids);
    }

    public function test_a_cover_only_invoice_keeps_no_detail_answers(): void
    {
        $this->actingAs($this->respondent())
            ->get(route('responses.create'))
            ->assertSeeInOrder(['基本情報', '請求書の構成', '鑑のみ', '鑑と明細', '鑑について'])
            ->assertSee('data-cover-only', false);

        // 明細 answers filled in before switching to 鑑のみ are dropped on save.
        $this->actingAs($this->respondent())
            ->post(route('responses.store'), [
                'employee_id' => Employee::factory()->create()->id,
                'customer_id' => Customer::factory()->create()->id,
                'office_id' => Office::factory()->create()->id,
                'invoice_composition_option_id' => $this->option('invoice_composition', 'cover_only'),
                'cover_item_ids' => [$this->option('billing_item', 'storage')],
                'creation_minutes' => 30,
                'detail_item_ids' => [$this->option('billing_item', 'other')],
                'detail_item_ids_other' => '使われない',
                'detail_format_option_id' => $this->option('detail_format', 'excel_own'),
                'detail_creation_minutes' => 60,
            ])
            ->assertSessionHasNoErrors();

        $answer = SurveyResponse::query()->sole();
        $this->assertTrue($answer->isCoverOnly());
        $this->assertSame([$this->option('billing_item', 'storage')], $answer->cover_item_ids);
        $this->assertSame([], $answer->detail_item_ids);
        $this->assertNull($answer->detail_format_option_id);
        $this->assertNull($answer->detail_creation_minutes);
        $this->assertSame(30, $answer->totalMinutes());

        $this->actingAs($this->respondent())
            ->get(route('responses.show', $answer))
            ->assertSeeInOrder(['請求書の構成', '鑑のみ', '鑑について'])
            ->assertDontSee('明細について');
    }

    public function test_ticking_other_asks_what_it_is(): void
    {
        $base = [
            'employee_id' => Employee::factory()->create()->id,
            'customer_id' => Customer::factory()->create()->id,
            'office_id' => Office::factory()->create()->id,
        ];

        $this->actingAs($this->respondent())
            ->get(route('responses.create'))
            ->assertSee('name="cover_item_ids_other"', false)
            ->assertSee('name="data_source_option_ids_other"', false);

        $this->actingAs($this->respondent())
            ->post(route('responses.store'), $base + [
                'cover_item_ids' => [$this->option('billing_item', 'storage'), $this->option('billing_item', 'other')],
                'cover_item_ids_other' => '梱包資材',
                'data_source_option_ids' => [$this->option('data_source', 'other')],
                'data_source_option_ids_other' => '顧客ポータルのCSV',
                // その他 is not ticked here, so the text is not kept.
                'detail_item_ids' => [$this->option('billing_item', 'storage')],
                'detail_item_ids_other' => '使われない',
            ])
            ->assertSessionHasNoErrors();

        $answer = SurveyResponse::query()->sole();
        $this->assertSame('梱包資材', $answer->otherText('cover_item_ids'));
        $this->assertNull($answer->otherText('detail_item_ids'));
        $this->assertDatabaseMissing('survey_response_choices', ['other_text' => '使われない']);

        $this->actingAs($this->respondent())
            ->get(route('responses.show', $answer))
            ->assertSeeInOrder(['鑑に載せている項目', '保管、その他（梱包資材）', '実績データの出どころ', 'その他（顧客ポータルのCSV）']);
    }

    public function test_an_option_from_another_dropdown_cannot_be_ticked_as_a_billing_item(): void
    {
        $this->actingAs($this->respondent())
            ->post(route('responses.store'), [
                'employee_id' => Employee::factory()->create()->id,
                'customer_id' => Customer::factory()->create()->id,
                'office_id' => Office::factory()->create()->id,
                'detail_item_ids' => [$this->option('record_timing', 'daily')],
            ])
            ->assertSessionHasErrors('detail_item_ids.0');

        $this->assertDatabaseCount('survey_responses', 0);
    }

    public function test_the_customer_dropdown_shows_how_many_answers_each_customer_already_has(): void
    {
        $answered = Customer::factory()->create(['code' => '1111', 'name' => '株式会社ＡＡＡＡ', 'sort_order' => 10]);
        Customer::factory()->create(['code' => '2222', 'name' => '株式会社ＢＢＢＢ', 'sort_order' => 20]);
        SurveyResponse::factory()->count(2)->for($answered)->create();

        $this->actingAs($this->respondent())
            ->get(route('responses.create'))
            ->assertOk()
            ->assertSeeInOrder(['株式会社ＡＡＡＡ（回答 2件）', '株式会社ＢＢＢＢ（未回答）'])
            ->assertDontSee('1111：');
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
