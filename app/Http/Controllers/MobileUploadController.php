<?php

namespace App\Http\Controllers;

use App\Jobs\AnalyzeDocumentJob;
use App\Models\Document;
use App\Models\UploadToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MobileUploadController extends Controller
{
    public function show(string $token): View
    {
        $uploadToken = UploadToken::where('token', $token)->firstOrFail();

        if (! $uploadToken->isValid()) {
            abort(410, 'This upload link has expired or has already been used.');
        }

        return view('upload', [
            'token' => $token,
            'visitReason' => $uploadToken->visit->reason_for_visit,
            'expiresAt' => $uploadToken->expires_at->toIso8601String(),
        ]);
    }

    public function store(Request $request, string $token): JsonResponse
    {
        $uploadToken = UploadToken::where('token', $token)->firstOrFail();

        if (! $uploadToken->isValid()) {
            return response()->json([
                'error' => 'This upload link has expired or has already been used.',
            ], 410);
        }

        $request->validate([
            'file' => ['required', 'file', 'max:20480', 'mimes:jpg,jpeg,png,gif,webp,pdf,heic,heif'],
            'document_type' => ['nullable', 'string', 'in:ecg,imaging,lab_result,photo,other'],
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());
        $visit = $uploadToken->visit;

        $contentType = match (true) {
            in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'heic', 'heif']) => 'image',
            $extension === 'pdf' => 'pdf',
            default => 'other',
        };

        $path = $file->store("documents/{$visit->id}", config('filesystems.upload'));

        $document = Document::create([
            'fhir_document_reference_id' => 'DocumentReference/'.Str::uuid(),
            'patient_id' => $visit->patient_id,
            'visit_id' => $visit->id,
            'title' => $request->input('title') ?: $file->getClientOriginalName(),
            'document_type' => $request->input('document_type', 'photo'),
            'content_type' => $contentType,
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'file_hash' => hash_file('sha256', $file->getRealPath()),
            'status' => 'current',
            'document_date' => now()->toDateString(),
            'confidentiality_level' => 'M',
            'created_by' => $uploadToken->created_by,
        ]);

        if (in_array($contentType, ['image', 'pdf'])) {
            AnalyzeDocumentJob::dispatch($document);
        }

        $uploadToken->markUsed($document);

        return response()->json(['data' => $document], 201);
    }
}
