<?php

namespace App\Services;

use App\Models\Project;

/**
 * Service handling financial calculations, cost synchronization,
 * and budget deviation metrics for construction projects.
 */
class ProjectCostService
{
    /**
     * Aggregate all project invoices and synchronize actual_cost in the database.
     *
     * @param Project $project
     * @return void
     */
    public function syncProjectCosts(Project $project): void
    {
        $totalInvoices = (float) $project->invoices()->sum('amount');

        $project->update([
            'actual_cost' => $totalInvoices,
        ]);
    }

    /**
     * Calculate financial budget deviation and risk metrics.
     *
     * @param Project $project
     * @return array
     */
    public function calculateDeviation(Project $project): array
    {
        $budget = (float) $project->budget;
        $actualCost = (float) $project->actual_cost;

        $deviationAmount = $actualCost - $budget;

        // Prevent division by zero if budget is zero
        $deviationPercentage = $budget > 0
            ? round(($deviationAmount / $budget) * 100, 2)
            : 0.0;

        $isOverBudget = $actualCost > $budget;

        return [
            'budget' => $budget,
            'actual_cost' => $actualCost,
            'deviation_amount' => $deviationAmount,
            'deviation_percentage' => $deviationPercentage,
            'is_over_budget' => $isOverBudget,
            'severity' => $this->determineSeverity($deviationPercentage),
        ];
    }

    /**
     * Determine risk severity level based on overrun percentage.
     *
     * @param float $percentage
     * @return string ('none' | 'medium' | 'high')
     */
    private function determineSeverity(float $percentage): string
    {
        if ($percentage <= 0) {
            return 'none';
        }

        if ($percentage <= 10) {
            return 'medium'; // Overrun up to 10%
        }

        return 'high'; // Critical overrun above 10%
    }
}
