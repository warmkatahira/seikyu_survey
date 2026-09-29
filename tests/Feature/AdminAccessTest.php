<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return list<array{string}>
     */
    public static function adminRoutes(): array
    {
        return [
            ['admin.dashboard'],
            ['admin.offices.index'],
            ['admin.employees.index'],
            ['admin.customers.index'],
            ['admin.choices.index'],
        ];
    }

    #[DataProvider('adminRoutes')]
    public function test_the_shared_answer_account_cannot_reach_the_admin_screens(string $routeName): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_RESPONDENT]))
            ->get(route($routeName))
            ->assertForbidden();
    }

    #[DataProvider('adminRoutes')]
    public function test_an_administrator_can_reach_the_admin_screens(string $routeName): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]))
            ->get(route($routeName))
            ->assertOk();
    }

    public function test_the_root_url_sends_a_guest_to_the_login_page(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_logging_in_lands_on_the_answer_list(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_RESPONDENT]);

        $this->post(route('login'), ['login_id' => $user->login_id, 'password' => 'password'])
            ->assertRedirect(route('responses.index'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_the_admin_account_is_filled_in_on_the_login_form_in_local_development(): void
    {
        $this->app->instance('env', 'local');

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('value="kanri"', escape: false)
            ->assertSee('開発環境のため');
    }

    public function test_no_credentials_are_filled_in_outside_local_development(): void
    {
        $this->app->instance('env', 'production');

        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('value="kanri"', escape: false)
            ->assertDontSee('開発環境のため');
    }

    public function test_a_wrong_password_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->post(route('login'), ['login_id' => $user->login_id, 'password' => 'wrong-password'])
            ->assertSessionHasErrors('login_id');

        $this->assertGuest();
    }
}
