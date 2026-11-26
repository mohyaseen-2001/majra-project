<?php

namespace Database\Factories;

use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Report> */
class ReportFactory extends Factory
{
    protected $model = Report::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->citizen(),
            'title' => fake()->randomElement(['تجمع مياه بعد هطول الأمطار', 'مجرى سيل قرب الطريق', 'ارتفاع منسوب المياه']),
            'description' => fake()->sentence(),
            'hazard_type' => 'flood',
            'risk_level' => fake()->randomElement(['low', 'medium', 'high']),
            'status' => 'pending',
            'source' => 'citizen',
            'area_name' => fake()->randomElement(['عمّان', 'الزرقاء', 'إربد', 'مادبا']),
            'latitude' => fake()->latitude(29.2, 33.4),
            'longitude' => fake()->longitude(34.9, 39.3),
            'expires_at' => now()->addHours(24),
        ];
    }
}
