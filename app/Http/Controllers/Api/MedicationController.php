<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MedicationController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet', 'endpoint' => 'medications.search']);
    }

    public function show(string $rxnormCode): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet', 'endpoint' => 'medications.show']);
    }

    public function interactions(string $rxnormCode): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet', 'endpoint' => 'medications.interactions']);
    }
}
