<?php

namespace Database\Factories;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => \App\Models\Project::factory(),
            'invoice_number' => 'INV-' . date('Y') . '-' . fake()->unique()->numerify('####'),
            'amount' => fake()->randomFloat(2, 2000, 25000),
            'status' => fake()->randomElement(['pending', 'paid', 'overdue']),
            'due_date' => fake()->dateTimeBetween('-1 month', '+2 months')->format('Y-m-d'),
        ];
    }

}
