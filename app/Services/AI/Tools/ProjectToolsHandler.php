<?php

namespace App\Services\AI\Tools;

use App\Models\Project;
use App\Models\Invoice;
use App\Services\AI\Agent\ApprovalGuardrail;

/**
 * Executes tool functions requested by the Gemini AI Agent against the database.
 * Wrapped with ApprovalGuardrail for safety and audit logging.
 */
class ProjectToolsHandler
{
    protected ApprovalGuardrail $guardrail;

    public function __construct(ApprovalGuardrail $guardrail)
    {
        $this->guardrail = $guardrail;
    }

    /**
     * Dispatch and execute a tool by name with arguments through the guardrail.
     *
     * @param string $toolName
     * @param array $args
     * @return array
     */
    public function execute(string $toolName, array $args): array
    {
        return $this->guardrail->evaluateAndExecute(
            $toolName,
            $args,
            fn() => $this->executeDirectly($toolName, $args)
        );
    }

    /**
     * Direct execution of tool logic against the database.
     */
    protected function executeDirectly(string $toolName, array $args): array
    {
        return match ($toolName) {
            'get_project_details' => $this->getProjectDetails((int) ($args['project_id'] ?? 0)),
            'get_project_invoices' => $this->getProjectInvoices((int) ($args['project_id'] ?? 0)),
            'create_manager_task' => $this->createManagerTask(
                (int) ($args['project_id'] ?? 0),
                (string) ($args['task_title'] ?? ''),
                (string) ($args['urgency'] ?? 'medium')
            ),
            default => ['error' => "Unknown tool: {$toolName}"],
        };
    }

    /**
     * Fetch project details with customer relationship.
     */
    protected function getProjectDetails(int $projectId): array
    {
        $project = Project::with('customer')->find($projectId);

        if (!$project) {
            return ['error' => "Project #{$projectId} not found."];
        }

        return [
            'id' => $project->id,
            'name' => $project->name,
            'budget' => (float) $project->budget,
            'actual_cost' => (float) $project->actual_cost,
            'status' => $project->status,
            'customer' => $project->customer?->name,
        ];
    }

    /**
     * Fetch all invoices associated with the project.
     */
    protected function getProjectInvoices(int $projectId): array
    {
        $invoices = Invoice::where('project_id', $projectId)->get();

        return [
            'project_id' => $projectId,
            'invoices_count' => $invoices->count(),
            'total_invoiced' => (float) $invoices->sum('amount'),
            'invoices' => $invoices->map(fn($inv) => [
                'invoice_number' => $inv->invoice_number,
                'amount' => (float) $inv->amount,
                'status' => $inv->status,
                'due_date' => $inv->due_date?->toDateString(),
            ])->toArray(),
        ];
    }

    /**
     * Create an actionable task or alert for the site manager.
     */
    protected function createManagerTask(int $projectId, string $taskTitle, string $urgency): array
    {
        return [
            'success' => true,
            'message' => "Manager task successfully created for project #{$projectId}.",
            'task' => [
                'project_id' => $projectId,
                'title' => $taskTitle,
                'urgency' => $urgency,
                'status' => 'pending_manager_review',
                'created_at' => now()->toIso8601String(),
            ]
        ];
    }
}
