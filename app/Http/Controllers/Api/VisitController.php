<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VisitController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet', 'endpoint' => 'visits.store']);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet', 'endpoint' => 'visits.show']);
    }

    public function summary(string $id): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet', 'endpoint' => 'visits.summary']);
    }
}
