<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Visit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ObservationController extends Controller
{
    public function index(Request $request, Visit $visit): JsonResponse
    {
        $query = $visit->observations();

        if ($request->has('category')) {
            $query->where('category', $request->input('category'));
        }

        $observations = $query->orderBy('effective_date', 'desc')
            ->orderBy('code_display')
            ->get();

        return response()->json(['data' => $observations]);
    }

    public function show(Visit $visit, string $observationId): JsonResponse
    {
        $observation = $visit->observations()->findOrFail($observationId);

        return response()->json(['data' => $observation]);
    }
}
