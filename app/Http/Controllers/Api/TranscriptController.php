<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TranscriptController extends Controller
{
    public function store(Request $request, string $visitId): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet', 'endpoint' => 'transcripts.store']);
    }

    public function show(string $visitId): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet', 'endpoint' => 'transcripts.show']);
    }

    public function process(string $visitId): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet', 'endpoint' => 'transcripts.process']);
    }

    public function status(string $visitId): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet', 'endpoint' => 'transcripts.status']);
    }
}
