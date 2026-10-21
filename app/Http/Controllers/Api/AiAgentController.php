<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiAuditLog;
use App\Services\AI\Agent\BouwflowAgent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class AiAgentController extends Controller
{
    /**
     * Run the autonomous ReAct agent loop for a natural language user prompt.
     * POST /api/ai/agent/run
     *
     * @param Request $request
     * @param BouwflowAgent $agent
     * @return JsonResponse
     */
    public function run(Request $request, BouwflowAgent $agent): JsonResponse
    {
        $validated = $request->validate([
            'prompt' => 'required|string|min:3|max:1000',
            'max_steps' => 'nullable|integer|min:1|max:10',
        ]);

        try {
            $result = $agent->run(
                $validated['prompt'],
                $validated['max_steps'] ?? 5
            );

            return response()->json($result, $result['success'] ? 200 : 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Agent execution encountered a critical error.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List AI audit logs with optional status filtering (e.g. ?status=pending_approval).
     * GET /api/ai/audit-logs
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function auditLogs(Request $request): JsonResponse
    {
        $status = $request->query('status');

        $logs = AiAuditLog::with('project')
            ->when($status, fn($query) => $query->where('status', $status))
            ->latest()
            ->paginate(15);

        return response()->json($logs);
    }

    /**
     * Approve a pending Human-in-the-Loop action staged by the AI Agent.
     * POST /api/ai/audit-logs/{auditLog}/approve
     *
     * @param AiAuditLog $auditLog
     * @return JsonResponse
     */
    public function approve(AiAuditLog $auditLog): JsonResponse
    {
        if ($auditLog->status !== 'pending_approval') {
            return response()->json([
                'message' => "Only actions with status 'pending_approval' can be approved.",
                'current_status' => $auditLog->status,
            ], 422);
        }

        $auditLog->update([
            'status' => 'approved',
        ]);

        return response()->json([
            'message' => "Action #{$auditLog->id} ({$auditLog->tool_name}) has been successfully approved by site supervisor.",
            'audit_log' => $auditLog,
        ], 200);
    }
}
