<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DemoController extends Controller
{
    public function start(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet', 'endpoint' => 'demo.start']);
    }

    public function status(): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet', 'endpoint' => 'demo.status']);
    }

    public function reset(): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet', 'endpoint' => 'demo.reset']);
    }

    public function simulateAlert(): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet', 'endpoint' => 'demo.simulateAlert']);
    }
}
