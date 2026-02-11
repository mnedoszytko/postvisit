<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVisitDocumentRequest;
use App\Jobs\AnalyzeDocumentJob;
use App\Models\Document;
use App\Models\Visit;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    public function show(Document $document): JsonResponse
    {
        $document->load(['patient:id,first_name,last_name', 'visit:id,started_at,reason_for_visit']);

        return response()->json(['data' => $document]);
    }

    public function store(StoreVisitDocumentRequest $request, Visit $visit): JsonResponse
    {
        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());

        // Map extension to content type category
        $contentType = match (true) {
            in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'heic', 'heif']) => 'image',
            $extension === 'pdf' => 'pdf',
            default => 'other',
        };

        $documentType = $request->input('document_type', 'other');
        $title = $request->input('title') ?: $file->getClientOriginalName();

        // Store in visit-scoped directory
        $path = $file->store(
            "documents/{$visit->id}",
            'local'
        );

        $document = Document::create([
            'fhir_document_reference_id' => 'DocumentReference/'.Str::uuid(),
            'patient_id' => $visit->patient_id,
            'visit_id' => $visit->id,
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

        // Dispatch AI analysis for images and PDFs
        if (in_array($contentType, ['image', 'pdf'])) {
            AnalyzeDocumentJob::dispatch($document);
        }

        return response()->json(['data' => $document], 201);
    }

    public function analysisStatus(Document $document): JsonResponse
    {
        return response()->json([
            'data' => [
                'analysis_status' => $document->analysis_status,
                'ai_analysis' => $document->ai_analysis,
                'analyzed_at' => $document->analyzed_at,
                'analysis_error' => $document->analysis_error,
            ],
        ]);
    }

    public function visitDocuments(Visit $visit): JsonResponse
    {
        $documents = $visit->documents()
            ->orderByDesc('document_date')
            ->get();

        return response()->json(['data' => $documents]);
    }

    public function download(Document $document): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        if (! Storage::disk('local')->exists($document->file_path)) {
            abort(404, 'File not found');
        }

        return Storage::disk('local')->download(
            $document->file_path,
            $document->title
        );
    }

    public function thumbnail(Document $document): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        if (! Storage::disk('local')->exists($document->file_path)) {
            abort(404, 'File not found');
        }

        $mime = Storage::disk('local')->mimeType($document->file_path);

        return Storage::disk('local')->response($document->file_path, null, [
            'Content-Type' => $mime,
            'Cache-Control' => 'max-age=86400',
        ]);
    }

    public function destroy(Document $document): JsonResponse
    {
        Storage::disk('local')->delete($document->file_path);
        $document->delete();

        return response()->json(['message' => 'Document deleted']);
    }
}
