<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function show(string $id): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet', 'endpoint' => 'patients.show']);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet', 'endpoint' => 'patients.update']);
    }

    public function visits(string $id): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet', 'endpoint' => 'patients.visits']);
    }

    public function conditions(string $id): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet', 'endpoint' => 'patients.conditions']);
    }

    public function addCondition(Request $request, string $id): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet', 'endpoint' => 'patients.addCondition']);
    }

    public function healthRecord(string $id): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet', 'endpoint' => 'patients.healthRecord']);
    }

    public function documents(string $id): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet', 'endpoint' => 'patients.documents']);
    }

    public function uploadDocument(Request $request, string $id): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet', 'endpoint' => 'patients.uploadDocument']);
    }
}
