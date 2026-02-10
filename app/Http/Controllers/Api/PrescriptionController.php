<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MedicationInteraction;
use App\Models\Patient;
use App\Models\Visit;
use Illuminate\Http\JsonResponse;

class PrescriptionController extends Controller
{
    public function visitPrescriptions(Visit $visit): JsonResponse
    {
        $prescriptions = $visit->prescriptions()
            ->with(['medication', 'practitioner:id,first_name,last_name'])
            ->get();

        return response()->json(['data' => $prescriptions]);
    }

    public function patientPrescriptions(Patient $patient): JsonResponse
    {
        $prescriptions = $patient->prescriptions()
            ->with(['medication', 'practitioner:id,first_name,last_name', 'visit:id,started_at,reason_for_visit'])
            ->orderByDesc('start_date')
            ->get();

        return response()->json(['data' => $prescriptions]);
    }

    public function patientInteractions(Patient $patient): JsonResponse
    {
        $medicationIds = $patient->prescriptions()
            ->where('status', 'active')
            ->pluck('medication_id')
            ->unique();

        $interactions = MedicationInteraction::where(function ($q) use ($medicationIds) {
            $q->whereIn('drug_a_id', $medicationIds)
                ->whereIn('drug_b_id', $medicationIds);
        })
            ->with(['drugA:id,display_name,generic_name', 'drugB:id,display_name,generic_name'])
            ->get();

        return response()->json(['data' => $interactions]);
    }
}
