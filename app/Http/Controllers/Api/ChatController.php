<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function sendMessage(Request $request, string $visitId): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet', 'endpoint' => 'chat.sendMessage']);
    }

    public function history(string $visitId): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet', 'endpoint' => 'chat.history']);
    }
}
