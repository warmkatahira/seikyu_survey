<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\SurveyResponse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SurveyResponse>
 */
class SurveyResponseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'customer_id' => Customer::factory(),
            'office_id' => null,
            'creation_minutes' => fake()->numberBetween(5, 180),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
