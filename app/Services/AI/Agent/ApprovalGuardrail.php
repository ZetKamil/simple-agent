<?php

namespace App\Services\AI\Agent;

use App\Models\AiAuditLog;

/**
 * Human-in-the-Loop guardrail interceptor.
 * Protects database state by requiring human approval for WRITE operations
 * while seamlessly allowing safe READ operations.
 */
class ApprovalGuardrail
{
    /**
     * High-risk or state-modifying tools requiring supervisor sign-off.
     */
    protected const WRITE_TOOLS = [
        'create_manager_task',
    ];

    /**
     * Intercept, audit, and evaluate tool calls before database execution.
     *
     * @param string $toolName
     * @param array $args
     * @param callable $executeDirectly Callback executing the actual tool logic
     * @return array
     */
    public function evaluateAndExecute(string $toolName, array $args, callable $executeDirectly): array
    {
        $isWrite = in_array($toolName, self::WRITE_TOOLS, true);
        $actionType = $isWrite ? 'write' : 'read';

        // 1. Guardrail Interception: Block autonomous WRITE actions
        if ($isWrite) {
            $log = AiAuditLog::create([
                'project_id' => $args['project_id'] ?? null,
                'tool_name' => $toolName,
                'action_type' => $actionType,
                'arguments' => $args,
                'result' => null,
                'status' => 'pending_approval',
            ]);

            return [
                'status' => 'pending_human_approval',
                'audit_log_id' => $log->id,
                'message' => "Action '{$toolName}' intercepted. Staged for site manager review in ai_audit_logs table.",
                'staged_action' => $args,
            ];
        }

        // 2. Safe READ operations: Execute immediately and persist audit trail
        $result = $executeDirectly();

        AiAuditLog::create([
            'project_id' => $args['project_id'] ?? null,
            'tool_name' => $toolName,
            'action_type' => $actionType,
            'arguments' => $args,
            'result' => $result,
            'status' => 'executed',
        ]);

        return $result;
    }
}
