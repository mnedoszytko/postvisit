<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Medications\DailyMedClient;
use App\Services\Medications\NihClinicalTablesClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MedicalLookupController extends Controller
{
    public function __construct(
        private NihClinicalTablesClient $nihClient,
        private DailyMedClient $dailyMedClient,
    ) {}

    public function searchConditions(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'min:2'],
        ]);

        $results = $this->nihClient->searchConditions($request->input('q'));

        return response()->json(['data' => $results]);
    }

    public function searchDrugs(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'min:2'],
        ]);

        $results = $this->nihClient->searchDrugs($request->input('q'));

        return response()->json(['data' => $results]);
    }

    public function searchProcedures(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'min:2'],
        ]);

        $results = $this->nihClient->searchProcedures($request->input('q'));

        return response()->json(['data' => $results]);
    }

    public function drugLabel(Request $request): JsonResponse
    {
        $request->validate([
            'drug_name' => ['required', 'string', 'min:2'],
        ]);

        $label = $this->dailyMedClient->searchSpls($request->input('drug_name'));

        return response()->json(['data' => $label]);
    }
}
