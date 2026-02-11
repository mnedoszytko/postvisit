<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UploadToken;
use App\Models\Visit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UploadTokenController extends Controller
{
    public function store(Request $request, Visit $visit): JsonResponse
    {
        $token = UploadToken::create([
            'token' => Str::uuid()->toString(),
            'visit_id' => $visit->id,
            'created_by' => $request->user()->id,
            'expires_at' => now()->addMinutes(15),
        ]);

        return response()->json([
            'data' => [
                'token' => $token->token,
                'url' => url("/upload/{$token->token}"),
                'expires_at' => $token->expires_at->toIso8601String(),
            ],
        ], 201);
    }

    public function status(string $token): JsonResponse
    {
        $uploadToken = UploadToken::where('token', $token)->firstOrFail();

        if ($uploadToken->used_at) {
            $uploadToken->load('document');

            return response()->json([
                'data' => [
                    'status' => 'completed',
                    'document' => $uploadToken->document,
                ],
            ]);
        }

        if ($uploadToken->expires_at->isPast()) {
            return response()->json([
                'data' => [
                    'status' => 'expired',
                    'document' => null,
                ],
            ]);
        }

        return response()->json([
            'data' => [
                'status' => 'pending',
                'document' => null,
            ],
        ]);
    }
}
