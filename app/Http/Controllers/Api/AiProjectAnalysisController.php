<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\AI\GeminiClient;
use App\Services\ProjectCostService;
use Illuminate\Http\JsonResponse;
use Exception;

class AiProjectAnalysisController extends Controller
{
    /**
     * Audit project budget and financial health using Gemini AI.
     * POST /api/ai/projects/{project}/analyze
     *
     * @param Project $project Resolved via Route Model Binding
     * @param ProjectCostService $costService Service calculating budget deviations
     * @param GeminiClient $geminiClient AI client with Structured Output
     * @return JsonResponse Combined financial metrics and AI audit report
     */
    public function analyze(
        Project $project,
        ProjectCostService $costService,
        GeminiClient $geminiClient
    ): JsonResponse {
        // 1. Eager load customer and invoices for comprehensive context
        $project->load(['customer', 'invoices']);

        // 2. Calculate financial deviation metrics
        $deviation = $costService->calculateDeviation($project);

        // 3. Prepare contextual business payload for AI
        $projectData = [
            'id' => $project->id,
            'name' => $project->name,
            'budget' => (float) $project->budget,
            'actual_cost' => (float) $project->actual_cost,
            'status' => $project->status,
            'customer_name' => $project->customer?->name,
            'invoices_count' => $project->invoices->count(),
        ];

        // 4. Request structured audit from Gemini with external service failure handling
        try {
            $aiAudit = $geminiClient->analyzeProjectBudget($projectData, $deviation);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'AI financial audit failed due to an external service error.',
                'error' => $e->getMessage(),
            ], 502);
        }

        // 5. Return combined response: deterministic financial metrics + AI audit
        return response()->json([
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'customer' => $project->customer?->name,
            ],
            'financial_metrics' => $deviation,
            'ai_audit' => $aiAudit,
        ], 200);
    }
}
