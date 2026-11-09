<?php

namespace Database\Factories;

use App\Models\Homeowner;
use App\Models\Project;
use App\Models\Subcategory;
use App\ProjectBudget;
use App\ProjectStatus;
use App\ProjectUrgency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'homeowner_id' => Homeowner::factory(),
            'subcategory_id' => Subcategory::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'city' => 'Omaha',
            'state' => 'Nebraska',
            'zip' => fake()->numerify('681##'),
            'urgency' => fake()->randomElement(ProjectUrgency::cases()),
            'budget' => fake()->randomElement(ProjectBudget::cases()),
            'status' => ProjectStatus::Open,
            'is_hidden' => false,
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn () => ['status' => ProjectStatus::InProgress]);
    }

    public function closed(): static
    {
        return $this->state(fn () => ['status' => ProjectStatus::Closed]);
    }

    public function hidden(): static
    {
        return $this->state(fn () => ['is_hidden' => true]);
    }
}
