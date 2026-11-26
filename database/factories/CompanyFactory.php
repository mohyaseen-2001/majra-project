<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Company> */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->company(),
            'name' => fake()->company(),
            'registration_number' => fake()->unique()->numerify('JO-########'),
            'business_type' => fake()->randomElement(['logistics', 'construction', 'transport']),
            'phone' => fake()->phoneNumber(),
            'status' => 'approved',
        ];
    }
}
