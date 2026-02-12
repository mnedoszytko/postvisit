<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePatientRequest;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function show(Patient $patient): JsonResponse
    {
        $this->authorize('view', $patient);

        $patient->load(['conditions', 'prescriptions.medication']);

        return response()->json(['data' => $patient]);
    }

    public function update(UpdatePatientRequest $request, Patient $patient): JsonResponse
    {
        $this->authorize('update', $patient);

        $patient->update($request->validated());

        return response()->json(['data' => $patient->fresh()]);
    }

    public function visits(Patient $patient): JsonResponse
    {
        $this->authorize('view', $patient);

        $visits = $patient->visits()
            ->with(['practitioner', 'organization'])
            ->orderByDesc('started_at')
            ->get();

        return response()->json(['data' => $visits]);
    }

    public function conditions(Patient $patient): JsonResponse
    {
        $this->authorize('view', $patient);

        $conditions = $patient->conditions()
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['data' => $conditions]);
    }

    public function addCondition(Request $request, Patient $patient): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet', 'endpoint' => 'patients.addCondition']);
    }

    public function healthRecord(Patient $patient): JsonResponse
    {
        $this->authorize('view', $patient);

        $patient->load([
            'conditions',
            'prescriptions.medication',
            'visits' => fn ($q) => $q->latest('started_at')->limit(5),
        ]);

        return response()->json(['data' => $patient]);
    }

    public function observations(Request $request, Patient $patient): JsonResponse
    {
        $this->authorize('view', $patient);

        $query = $patient->observations()->orderByDesc('effective_date');

        if ($request->has('code')) {
            $query->where('code', $request->input('code'));
        }

        if ($request->has('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->has('limit')) {
            $query->limit((int) $request->input('limit'));
        }

        return response()->json(['data' => $query->get()]);
    }

    public function documents(Patient $patient): JsonResponse
    {
        $this->authorize('view', $patient);

        $documents = $patient->documents()->orderByDesc('document_date')->get();

        return response()->json(['data' => $documents]);
    }

    public function uploadDocument(Request $request, Patient $patient): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet', 'endpoint' => 'patients.uploadDocument']);
    }
}
