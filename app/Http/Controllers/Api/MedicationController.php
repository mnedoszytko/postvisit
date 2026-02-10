<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Medication;
use App\Models\MedicationInteraction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MedicationController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'min:2'],
        ]);

        $query = $request->input('q');

        $medications = Medication::where('generic_name', 'ilike', "%{$query}%")
            ->orWhere('display_name', 'ilike', "%{$query}%")
            ->orWhere('rxnorm_code', $query)
            ->where('is_active', true)
            ->limit(20)
            ->get();

        return response()->json(['data' => $medications]);
    }

    public function show(string $rxnormCode): JsonResponse
    {
        $medication = Medication::where('rxnorm_code', $rxnormCode)->first();

        if (! $medication) {
            return response()->json(['error' => ['message' => 'Medication not found']], 404);
        }

        return response()->json(['data' => $medication]);
    }

    public function interactions(string $rxnormCode): JsonResponse
    {
        $medication = Medication::where('rxnorm_code', $rxnormCode)->first();

        if (! $medication) {
            return response()->json(['error' => ['message' => 'Medication not found']], 404);
        }

        $interactions = MedicationInteraction::where('drug_a_id', $medication->id)
            ->orWhere('drug_b_id', $medication->id)
            ->with(['drugA:id,display_name,generic_name,rxnorm_code', 'drugB:id,display_name,generic_name,rxnorm_code'])
            ->get();

        return response()->json(['data' => $interactions]);
    }
}
