<?php

namespace Database\Factories;

use App\Models\TenantAvailability;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TenantAvailability>
 */
class TenantAvailabilityFactory extends Factory
{
    /**
     * Define the model's default state. A tenant_id must be provided by the caller.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'is_active' => true,
            'timezone' => 'America/New_York',
            'slots' => [
                ['day' => 1, 'start' => '18:00', 'end' => '22:00'],
                ['day' => 6, 'start' => '08:00', 'end' => '14:00'],
            ],
            'note' => fake()->sentence(4),
            'hourly_rate' => fake()->randomFloat(2, 15, 40),
        ];
    }

    public function paused(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    /**
     * Available every day of the week for the whole day.
     */
    public function alwaysAvailable(): static
    {
        return $this->state(fn () => [
            'slots' => collect(range(0, 6))->map(fn (int $day) => ['day' => $day, 'start' => '00:00', 'end' => '23:59'])->all(),
        ]);
    }
}
