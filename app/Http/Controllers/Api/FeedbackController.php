<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function store(Request $request, string $visitId): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet', 'endpoint' => 'feedback.store']);
    }

    public function index(string $visitId): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet', 'endpoint' => 'feedback.index']);
    }

    public function markRead(string $id): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet', 'endpoint' => 'feedback.markRead']);
    }
}
