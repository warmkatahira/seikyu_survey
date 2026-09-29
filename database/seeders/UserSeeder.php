<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Also pre-filled on the login form in local development; see LoginController.
     */
    public const ADMIN_PASSWORD = 'seikyu_survey_warm';

    /**
     * Seeds the two accounts the survey runs on: one administrator, and one shared
     * account every respondent logs into (they identify themselves by picking their
     * name from the employee master on the answer form).
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['login_id' => 'kanri'],
            [
                'name' => '管理者',
                'role' => User::ROLE_ADMIN,
                'password' => Hash::make(self::ADMIN_PASSWORD),
            ],
        );

        User::updateOrCreate(
            ['login_id' => 'warm'],
            [
                'name' => '回答用アカウント',
                'role' => User::ROLE_RESPONDENT,
                'password' => Hash::make('password'),
            ],
        );
    }
}
