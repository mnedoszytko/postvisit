<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MedicalReference;
use App\Services\Medications\PubMedClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReferenceController extends Controller
{
    public function __construct(
        private PubMedClient $pubMedClient,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = MedicalReference::query();

        if ($request->has('specialty')) {
            $query->bySpecialty($request->input('specialty'));
        }
        if ($request->has('category')) {
            $query->where('category', $request->input('category'));
        }
        if ($request->has('verified')) {
            $query->verified();
        }

        $references = $query->orderBy('year', 'desc')->get();

        return response()->json(['data' => $references]);
    }

    public function show(MedicalReference $reference): JsonResponse
    {
        return response()->json(['data' => $reference]);
    }

    public function verify(MedicalReference $reference): JsonResponse
    {
        $verification = null;

        if ($reference->pmid) {
            $verification = $this->pubMedClient->verifyPmid($reference->pmid);
        } elseif ($reference->doi) {
            $verification = $this->pubMedClient->searchByDoi($reference->doi);
        }

        if ($verification && $verification['exists']) {
            $reference->update([
                'verified' => true,
                'verified_at' => now(),
            ]);
        }

        return response()->json([
            'data' => [
                'reference' => $reference->fresh(),
                'pubmed_result' => $verification,
                'verified' => isset($verification['exists']) && $verification['exists'],
            ],
        ]);
    }

    public function verifyPmid(Request $request): JsonResponse
    {
        $request->validate([
            'pmid' => ['required', 'string'],
        ]);

        $result = $this->pubMedClient->verifyPmid($request->input('pmid'));

        return response()->json([
            'data' => $result ?? ['exists' => false, 'pmid' => $request->input('pmid')],
        ]);
    }
}
