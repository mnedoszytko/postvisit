<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Practitioner;
use App\Models\Visit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VisitController extends Controller
{
    public function practitioners(): JsonResponse
    {
        $practitioners = Practitioner::select('id', 'first_name', 'last_name', 'primary_specialty', 'medical_degree')
            ->orderBy('last_name')
            ->get();

        return response()->json(['data' => $practitioners]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'patient_id' => ['required', 'uuid', 'exists:patients,id'],
            'practitioner_id' => ['required', 'uuid', 'exists:practitioners,id'],
            'organization_id' => ['nullable', 'uuid', 'exists:organizations,id'],
            'visit_type' => ['required', 'in:office_visit,telehealth,emergency,inpatient'],
            'reason_for_visit' => ['required', 'string'],
            'started_at' => ['required', 'date'],
        ]);

        $validated['fhir_encounter_id'] = 'Encounter/'.\Illuminate\Support\Str::uuid();
        $validated['visit_status'] = 'in_progress';
        $validated['class'] = $validated['class'] ?? 'AMB';
        $validated['created_by'] = $request->user()->id;

        if (empty($validated['organization_id'])) {
            $validated['organization_id'] = Practitioner::find($validated['practitioner_id'])?->organization_id;
        }

        $visit = Visit::create($validated);
        $visit->load(['patient', 'practitioner', 'organization']);

        return response()->json(['data' => $visit], 201);
    }

    public function show(Visit $visit): JsonResponse
    {
        $visit->load([
            'patient',
            'practitioner',
            'organization',
            'observations',
            'conditions',
            'prescriptions.medication',
            'documents',
            'transcript',
            'visitNote',
        ]);

        return response()->json(['data' => $visit]);
    }

    public function summary(Visit $visit): JsonResponse
    {
        $visit->load([
            'patient:id,first_name,last_name',
            'practitioner:id,first_name,last_name,primary_specialty',
            'conditions:id,visit_id,code_display,clinical_status,severity',
            'prescriptions:id,visit_id,medication_id,dose_quantity,dose_unit,frequency',
            'prescriptions.medication:id,display_name,generic_name',
            'visitNote:id,visit_id,chief_complaint,assessment,plan,follow_up',
        ]);

        return response()->json(['data' => $visit]);
    }
}
