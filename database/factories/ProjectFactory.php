<?php

namespace Database\Factories;

use App\Models\Project;
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
        $budget = fake()->numberBetween(20000, 100000);

        return [
            'customer_id' => \App\Models\Customer::factory(),
            'name' => fake()->randomElement(['Renovatie woning', 'Nieuwbouw kantoor', 'Dakrenovatie', 'Aanbouw keuken']) . ' ' . fake()->city(),
            'budget' => $budget,
            'actual_cost' => 0,
            'status' => fake()->randomElement(['in_progress', 'completed', 'paused']),
        ];
    }

}
