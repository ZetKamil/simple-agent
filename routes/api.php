<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\AiProjectAnalysisController;
use App\Http\Controllers\Api\AiAgentController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::get('health', [HealthController::class, 'check']);

Route::apiResource('customers', CustomerController::class);
Route::apiResource('projects', ProjectController::class);


Route::post('/ai/projects/{project}/analyze', [AiProjectAnalysisController::class, 'analyze']);
// Autonomous ReAct AI Agent
Route::post('/ai/agent/run', [AiAgentController::class, 'run']);
Route::get('/ai/audit-logs', [AiAgentController::class, 'auditLogs']);
Route::post('/ai/audit-logs/{auditLog}/approve', [AiAgentController::class, 'approve']);
