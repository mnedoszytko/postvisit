<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class DocumentController extends Controller
{
    public function show(string $id): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet', 'endpoint' => 'documents.show']);
    }
}
