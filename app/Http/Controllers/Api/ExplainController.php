<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Visit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExplainController extends Controller
{
    public function explain(Request $request, Visit $visit): JsonResponse
    {
        $validated = $request->validate([
            'term' => ['required', 'string', 'max:500'],
            'context' => ['nullable', 'string', 'max:2000'],
        ]);

        // TODO: Use MedicalExplainer service with SSE streaming
        // For now, return a placeholder response
        return response()->json([
            'data' => [
                'term' => $validated['term'],
                'explanation' => 'AI explanations will be available once the Anthropic API key is configured.',
                'visit_id' => $visit->id,
                'source' => 'placeholder',
            ],
        ]);
    }
}
