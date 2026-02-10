<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    public function dashboard(): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet', 'endpoint' => 'doctor.dashboard']);
    }

    public function patients(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet', 'endpoint' => 'doctor.patients']);
    }

    public function patientDetail(string $id): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet', 'endpoint' => 'doctor.patientDetail']);
    }

    public function patientVisits(string $id): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet', 'endpoint' => 'doctor.patientVisits']);
    }

    public function engagement(string $id): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet', 'endpoint' => 'doctor.engagement']);
    }

    public function chatAudit(string $id): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet', 'endpoint' => 'doctor.chatAudit']);
    }

    public function notifications(): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet', 'endpoint' => 'doctor.notifications']);
    }

    public function reply(Request $request, string $id): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet', 'endpoint' => 'doctor.reply']);
    }
}
