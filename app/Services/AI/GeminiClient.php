<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Service client for Google Gemini REST API.
 * Handles authentication, HTTP resilience (timeout, retry, SSL handling),
 * and structured JSON outputs via responseSchema.
 */
class GeminiClient
{
    protected string $apiKey;
    protected string $model;
    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';

    /**
     * Initialize Gemini API credentials and model configuration.
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->apiKey = (string) config('services.gemini.key');
        $this->model = (string) config('services.gemini.model', 'gemini-3.5-flash-lite');

        // Fail fast if API key is not configured
        if (empty($this->apiKey)) {
            throw new Exception('Missing GEMINI_API_KEY in .env configuration.');
        }
    }

    /**
     * Send a basic prompt and return plain text response.
     *
     * @param string $prompt
     * @return string
     */
    public function generateText(string $prompt): string
    {
        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ]
        ];

        $response = $this->callApi($payload);

        return $response['candidates'][0]['content']['parts'][0]['text'] ?? '';
    }

    /**
     * Analyze project budget using Structured Output (JSON Schema enforcement).
     *
     * @param array $projectData
     * @param array $deviationData
     * @return array
     */
    public function analyzeProjectBudget(array $projectData, array $deviationData): array
    {
        $prompt = "Analyze the financial state of this Bouwflow construction project.\n"
            . "Project Data: " . json_encode($projectData, JSON_UNESCAPED_UNICODE) . "\n"
            . "Calculated Deviations: " . json_encode($deviationData, JSON_UNESCAPED_UNICODE) . "\n"
            . "Provide a concise risk summary and recommended action for the site manager.";

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'systemInstruction' => [
                'parts' => [
                    ['text' => 'You are an ERP expert and financial auditor for Bouwflow construction projects. Always respond strictly in valid JSON format matching the schema.']
                ]
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'responseSchema' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'status' => [
                            'type' => 'STRING',
                            'enum' => ['on_track', 'at_risk', 'over_budget']
                        ],
                        'risk_summary' => [
                            'type' => 'STRING',
                            'description' => 'Single sentence summary of the current financial risk.'
                        ],
                        'recommended_action' => [
                            'type' => 'STRING',
                            'description' => 'Specific recommendation for the project manager.'
                        ],
                        'alert_required' => [
                            'type' => 'BOOLEAN',
                            'description' => 'Whether an immediate executive alert is required.'
                        ]
                    ],
                    'required' => ['status', 'risk_summary', 'recommended_action', 'alert_required']
                ]
            ]
        ];

        $response = $this->callApi($payload);
        $jsonText = $response['candidates'][0]['content']['parts'][0]['text'] ?? '{}';

        return json_decode($jsonText, true) ?? [];
    }

    /**
     * Execute HTTP POST request to Google Gemini REST endpoint.
     * Includes timeout, retry policies, and SSL bypass for local development.
     *
     * @param array $payload
     * @return array
     * @throws Exception
     */
    protected function callApi(array $payload): array
    {
        $url = "{$this->baseUrl}/{$this->model}:generateContent?key={$this->apiKey}";

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])
            // Bypass SSL certificate verification only in local environment (Windows/WAMP development)
            ->when(app()->isLocal(), fn ($http) => $http->withoutVerifying())
            ->timeout(30)
            ->retry(3, 1000)
            ->post($url, $payload);

        if (!$response->successful()) {
            Log::error('Gemini API Error: ' . $response->body());
            throw new Exception("Gemini API communication error (Status {$response->status()}): {$response->body()}");
        }

        return $response->json();
    }
}
