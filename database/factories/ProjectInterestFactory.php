<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectInterest;
use App\Models\Tenant;
use App\ProjectInterestStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProjectInterest>
 */
class ProjectInterestFactory extends Factory
{
    /**
     * Define the model's default state. The tenant is created without events so no
     * tenant database is provisioned.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'tenant_id' => fn () => Tenant::withoutEvents(fn () => Tenant::create([
                'id' => 'contractor'.Str::lower(Str::random(8)),
                'name' => fake()->company(),
                'email' => fake()->unique()->companyEmail(),
                'status' => 'active',
                'plan' => 'pro',
            ]))->id,
            'message' => fake()->sentence(12),
            'status' => ProjectInterestStatus::Pending,
        ];
    }

    public function accepted(): static
    {
        return $this->state(fn () => ['status' => ProjectInterestStatus::Accepted, 'responded_at' => now()]);
    }

    public function declined(): static
    {
        return $this->state(fn () => ['status' => ProjectInterestStatus::Declined, 'responded_at' => now()]);
    }
}
