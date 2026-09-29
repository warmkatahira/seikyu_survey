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
                // A 締め日 option offered where the 保管料の課金方式 dropdown is expected.
                'storage_billing_method_option_id' => $this->option('closing_day', 'month_end'),
            ])
            ->assertSessionHasErrors('storage_billing_method_option_id');

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

    public function test_an_answer_keeps_showing_an_option_that_was_later_deactivated(): void
    {
        $deactivated = ChoiceOption::query()->firstWhere('value', 'per_pallet');
        $answer = SurveyResponse::factory()->create(['storage_billing_method_option_id' => $deactivated->id]);

        $deactivated->update(['is_active' => false]);

        $this->actingAs($this->respondent())
            ->get(route('responses.edit', $answer))
            ->assertOk()
            ->assertSee('パレット建て（無効）');
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
