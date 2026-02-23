<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePatientRequest;
use App\Jobs\AnalyzeDocumentJob;
use App\Models\Document;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PatientController extends Controller
{
    public function show(Patient $patient): JsonResponse
    {
        $patient->load(['conditions', 'prescriptions.medication']);

        return response()->json(['data' => $patient]);
    }

    public function update(UpdatePatientRequest $request, Patient $patient): JsonResponse
    {
        $patient->update($request->validated());

        return response()->json(['data' => $patient->fresh()]);
    }

    public function visits(Patient $patient): JsonResponse
    {
        $visits = $patient->visits()
            ->with(['practitioner', 'organization'])
            ->orderBy('started_at')
            ->get();

        return response()->json(['data' => $visits]);
    }

    public function conditions(Patient $patient): JsonResponse
    {
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
        $patient->load([
            'conditions',
            'prescriptions.medication',
            'visits' => fn ($q) => $q->latest('started_at')->limit(5),
        ]);

        return response()->json(['data' => $patient]);
    }

    public function observations(Request $request, Patient $patient): JsonResponse
    {
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
        $documents = $patient->documents()->orderByDesc('document_date')->get();

        return response()->json(['data' => $documents]);
    }

    public function uploadDocument(Request $request, Patient $patient): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:20480', 'mimes:jpg,jpeg,png,gif,webp,pdf,heic,heif'],
            'title' => ['nullable', 'string', 'max:255'],
            'document_type' => ['nullable', 'string', 'in:lab_result,imaging_report,discharge_summary,prescription,other'],
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());

        $contentType = match (true) {
            in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'heic', 'heif']) => 'image',
            $extension === 'pdf' => 'pdf',
            default => 'other',
        };

        $documentType = $request->input('document_type') ?? match ($contentType) {
            'pdf' => 'lab_result',
            'image' => 'imaging_report',
            default => 'other',
        };

        $title = $request->input('title') ?: $file->getClientOriginalName();

        $path = $file->store(
            "documents/patient/{$patient->id}",
            config('filesystems.upload')
        );

        $document = Document::create([
            'fhir_document_reference_id' => 'DocumentReference/'.Str::uuid(),
            'patient_id' => $patient->id,
            'visit_id' => null,
            'title' => $title,
            'document_type' => $documentType,
            'content_type' => $contentType,
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'file_hash' => hash_file('sha256', $file->getRealPath()),
            'status' => 'current',
            'document_date' => now()->toDateString(),
            'confidentiality_level' => 'M',
            'created_by' => $request->user()->id,
        ]);

        if (in_array($contentType, ['image', 'pdf'])) {
            AnalyzeDocumentJob::dispatch($document);
        }

        return response()->json(['data' => $document], 201);
    }
}
