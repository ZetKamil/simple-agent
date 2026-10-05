<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    public function check(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'php_version' => PHP_VERSION,
            'time' => now()->toIso8601String()
        ]);
    }
}
