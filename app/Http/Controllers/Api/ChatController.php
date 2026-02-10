<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\Visit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function sendMessage(Request $request, Visit $visit): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        // Find or create a chat session for this visit
        $session = ChatSession::firstOrCreate(
            ['visit_id' => $visit->id, 'patient_id' => $visit->patient_id],
            [
                'topic' => 'Post-visit follow-up',
                'status' => 'active',
                'initiated_at' => now(),
            ]
        );

        // Save the user message
        $userMessage = ChatMessage::create([
            'session_id' => $session->id,
            'sender_type' => 'patient',
            'message_text' => $validated['message'],
            'created_at' => now(),
        ]);

        // TODO: Generate AI response via QaAssistant service (SSE streaming)
        // For now, return a placeholder AI response
        $aiMessage = ChatMessage::create([
            'session_id' => $session->id,
            'sender_type' => 'ai',
            'message_text' => 'Thank you for your question. AI responses will be available once the Anthropic API key is configured.',
            'ai_model_used' => 'placeholder',
            'created_at' => now(),
        ]);

        return response()->json([
            'data' => [
                'user_message' => $userMessage,
                'ai_message' => $aiMessage,
                'session_id' => $session->id,
            ],
        ]);
    }

    public function history(Visit $visit): JsonResponse
    {
        $session = ChatSession::where('visit_id', $visit->id)
            ->where('patient_id', $visit->patient_id)
            ->first();

        if (! $session) {
            return response()->json(['data' => ['messages' => [], 'session' => null]]);
        }

        $messages = $session->messages()
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'data' => [
                'session' => $session,
                'messages' => $messages,
            ],
        ]);
    }
}
