<?php

namespace App\Services\AI\Tools;

/**
 * Declares OpenAPI/JSON Schema function tools available to the Gemini AI Agent.
 * The model reads these descriptions to autonomously decide when and how to call them.
 */
class ProjectToolsDeclaration
{
    /**
     * Get the full Gemini tools payload definition.
     *
     * @return array
     */
    public static function getTools(): array
    {
        return [
            [
                'functionDeclarations' => [
                    self::getProjectDetailsDeclaration(),
                    self::getProjectInvoicesDeclaration(),
                    self::createManagerTaskDeclaration(),
                ]
            ]
        ];
    }

    /**
     * Tool: Retrieve project financial data, budget, status and client.
     */
    private static function getProjectDetailsDeclaration(): array
    {
        return [
            'name' => 'get_project_details',
            'description' => 'Retrieve comprehensive details for a specific construction project including budget, actual cost, status, and client information.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'project_id' => [
                        'type' => 'INTEGER',
                        'description' => 'The unique database ID of the construction project.',
                    ],
                ],
                'required' => ['project_id'],
            ],
        ];
    }

    /**
     * Tool: Retrieve all invoices belonging to a project.
     */
    private static function getProjectInvoicesDeclaration(): array
    {
        return [
            'name' => 'get_project_invoices',
            'description' => 'Retrieve all invoices associated with a specific construction project, including invoice numbers, amounts, due dates, and payment statuses.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'project_id' => [
                        'type' => 'INTEGER',
                        'description' => 'The unique database ID of the construction project.',
                    ],
                ],
                'required' => ['project_id'],
            ],
        ];
    }

    /**
     * Tool: Create an actionable task or alert for the site manager.
     */
    private static function createManagerTaskDeclaration(): array
    {
        return [
            'name' => 'create_manager_task',
            'description' => 'Create an actionable task or urgent alert for the construction project manager when financial risks or schedule delays are identified.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'project_id' => [
                        'type' => 'INTEGER',
                        'description' => 'The project ID associated with this task.',
                    ],
                    'task_title' => [
                        'type' => 'STRING',
                        'description' => 'Clear, actionable title of the task.',
                    ],
                    'urgency' => [
                        'type' => 'STRING',
                        'enum' => ['low', 'medium', 'high', 'critical'],
                        'description' => 'Priority level of the required action.',
                    ],
                ],
                'required' => ['project_id', 'task_title', 'urgency'],
            ],
        ];
    }
}
