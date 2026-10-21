<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\ProjectController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::get('health', [HealthController::class, 'check']);

Route::apiResource('customers', CustomerController::class);
Route::apiResource('projects', ProjectController::class);
