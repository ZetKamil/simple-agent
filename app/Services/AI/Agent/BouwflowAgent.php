<?php

namespace App\Services\AI\Agent;

use App\Services\AI\Tools\ProjectToolsDeclaration;
use App\Services\AI\Tools\ProjectToolsHandler;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Autonomous ReAct (Reasoning + Acting) Agent for Bouwflow ERP.
 * Orchestrates multi-step decision loops by calling tools against the database
 * until a final grounded answer is produced.
 */
class BouwflowAgent
{
    protected string $apiKey;
    protected string $model;
    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';
    protected ProjectToolsHandler $toolsHandler;

    public function __construct(ProjectToolsHandler $toolsHandler)
    {
        $this->apiKey = (string) config('services.gemini.key');
        $this->model = (string) config('services.gemini.model', 'gemini-3.5-flash-lite');
        $this->toolsHandler = $toolsHandler;

        if (empty($this->apiKey)) {
            throw new Exception('Missing GEMINI_API_KEY in configuration.');
        }
    }

    /**
     * Execute the autonomous agent loop.
     *
     * @param string $userPrompt The instruction for the agent
     * @param int $maxSteps Safety ceiling to prevent infinite execution loops
     * @return array
     */
    public function run(string $userPrompt, int $maxSteps = 5): array
    {
        // 1. Initialize conversation history with user instruction and system persona
        $contents = [
            [
                'role' => 'user',
                'parts' => [
                    ['text' => $userPrompt]
                ]
            ]
        ];

        $tools = ProjectToolsDeclaration::getTools();
        $executionLog = [];

        // 2. Autonomous ReAct Loop
        for ($step = 1; $step <= $maxSteps; $step++) {
            $response = $this->callGemini($contents, $tools);

            $candidate = $response['candidates'][0] ?? null;
            if (!$candidate) {
                throw new Exception('Gemini returned an empty response.');
            }

            $modelTurn = $candidate['content'];
            $contents[] = $modelTurn; // Keep exact model turn in history (includes thought signatures)

            $firstPart = $modelTurn['parts'][0] ?? [];

            // Case A: Model requested a tool execution (functionCall)
            if (isset($firstPart['functionCall'])) {
                $functionCall = $firstPart['functionCall'];
                $toolName = $functionCall['name'];
                $toolArgs = $functionCall['args'] ?? [];

                $executionLog[] = [
                    'step' => $step,
                    'action' => 'tool_call',
                    'tool' => $toolName,
                    'args' => $toolArgs,
                ];

                // Execute the tool locally via our handler
                $toolResult = $this->toolsHandler->execute($toolName, $toolArgs);

                // Append functionResponse turn to conversation history
                $contents[] = [
                    'role' => 'user',
                    'parts' => [
                        [
                            'functionResponse' => [
                                'name' => $toolName,
                                'response' => [
                                    'output' => $toolResult
                                ]
                            ]
                        ]
                    ]
                ];

                // Continue loop: model will evaluate the tool result on next iteration
                continue;
            }

            // Case B: Model completed reasoning and provided final text answer
            if (isset($firstPart['text'])) {
                $executionLog[] = [
                    'step' => $step,
                    'action' => 'final_answer',
                ];

                return [
                    'success' => true,
                    'final_answer' => $firstPart['text'],
                    'steps_count' => $step,
                    'execution_log' => $executionLog,
                ];
            }
        }

        return [
            'success' => false,
            'message' => "Agent reached the maximum limit of {$maxSteps} steps without completing.",
            'execution_log' => $executionLog,
        ];
    }

    /**
     * Send HTTP POST request to Google Gemini with tools and history.
     */
    protected function callGemini(array $contents, array $tools): array
    {
        $url = "{$this->baseUrl}/{$this->model}:generateContent?key={$this->apiKey}";

        $payload = [
            'contents' => $contents,
            'tools' => $tools,
            'systemInstruction' => [
                'parts' => [
                    ['text' => 'You are an autonomous Bouwflow AI construction project supervisor. Inspect projects, verify invoices, and create manager tasks when budget overruns occur.']
                ]
            ]
        ];

        $response = Http::withHeaders(['Content-Type' => 'application/json'])
            ->when(app()->isLocal(), fn($http) => $http->withoutVerifying())
            ->timeout(30)
            ->retry(3, 1000)
            ->post($url, $payload);

        if (!$response->successful()) {
            Log::error('BouwflowAgent API Error: ' . $response->body());
            throw new Exception("Gemini Agent communication error (Status {$response->status()}): {$response->body()}");
        }

        return $response->json();
    }
}
