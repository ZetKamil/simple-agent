<?php

namespace App\Services;

use App\Models\Project;

class ProjectCostService
{
    /**
     * Zsumuj wszystkie faktury i zsynchronizuj actual_cost projektu w bazie.
     */
    public function syncProjectCosts(Project $project): void
    {
        $totalInvoices = (float) $project->invoices()->sum('amount');

        $project->update([
            'actual_cost' => $totalInvoices,
        ]);
    }

    /**
     * Wylicz matematyczną analizę budżetu pod kątem ryzyka dla Agenta AI.
     */
    public function calculateDeviation(Project $project): array
    {
        $budget = (float) $project->budget;
        $actualCost = (float) $project->actual_cost;

        $deviationAmount = $actualCost - $budget;

        // Zabezpieczenie przed dzieleniem przez zero, gdyby budżet był 0:
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
     * Określ poziom ryzyka (low, medium, high) dla Agenta AI.
     */
    private function determineSeverity(float $percentage): string
    {
        if ($percentage <= 0) {
            return 'none';
        }

        if ($percentage <= 10) {
            return 'medium'; // Przekroczenie do 10%
        }

        return 'high'; // Przekroczenie powyżej 10% — alarm dla kierownika!
    }
}
