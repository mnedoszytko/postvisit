<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\JsonResponse;

class DocumentController extends Controller
{
    public function show(Document $document): JsonResponse
    {
        $document->load(['patient:id,first_name,last_name', 'visit:id,started_at,reason_for_visit']);

        return response()->json(['data' => $document]);
    }
}
