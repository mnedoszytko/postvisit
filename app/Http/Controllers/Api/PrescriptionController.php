<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class PrescriptionController extends Controller
{
    public function visitPrescriptions(string $visitId): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet', 'endpoint' => 'prescriptions.visitPrescriptions']);
    }

    public function patientPrescriptions(string $patientId): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet', 'endpoint' => 'prescriptions.patientPrescriptions']);
    }

    public function patientInteractions(string $patientId): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet', 'endpoint' => 'prescriptions.patientInteractions']);
    }
}
