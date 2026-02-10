<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExplainController extends Controller
{
    public function explain(Request $request, string $visitId): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet', 'endpoint' => 'explain.explain']);
    }
}
