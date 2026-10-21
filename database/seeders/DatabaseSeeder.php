<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Project;
use App\Models\Invoice;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Customer::factory(10)->create()->each(function (Customer $customer) {
            $projects = Project::factory(rand(2, 4))->create([
                'customer_id' => $customer->id,
            ]);

            foreach ($projects as $project) {
                $invoices = Invoice::factory(rand(2, 4))->create([
                    'project_id' => $project->id,
                ]);

                // Sync project actual_cost with the total sum of generated invoices:
                $project->update([
                    'actual_cost' => $invoices->sum('amount'),
                ]);
            }
        });
    }
}
