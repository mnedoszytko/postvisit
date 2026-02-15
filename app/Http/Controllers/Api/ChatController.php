<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\Visit;
use App\Services\AI\AiTierManager;
use App\Services\AI\QaAssistant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatController extends Controller
{
    public function __construct(
        private QaAssistant $qaAssistant,
    ) {}

    public function sendMessage(Request $request, Visit $visit): StreamedResponse
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
        ChatMessage::create([
            'session_id' => $session->id,
            'sender_type' => 'patient',
            'message_text' => $validated['message'],
            'created_at' => now(),
        ]);

        // Load visit relationships for context
        $visit->load(['patient', 'practitioner', 'visitNote', 'observations', 'conditions', 'prescriptions.medication', 'transcript']);

        // AI streaming can take >30s with extended thinking — remove PHP time limit
        set_time_limit(0);

        // Release session lock before streaming — prevents blocking concurrent requests
        session()->save();

        return response()->stream(function () use ($session, $validated) {
            // Allow streaming to continue even if client disconnects
            ignore_user_abort(true);

            // Disable PHP output buffering for true SSE streaming
            if (! headers_sent()) {
                while (ob_get_level() > 0) {
                    ob_end_flush();
                }
                @ini_set('zlib.output_compression', '0');
                @ini_set('implicit_flush', '1');
            }

            $fullResponse = '';
            $thinkingContent = '';

            try {
                foreach ($this->qaAssistant->answer($session, $validated['message']) as $chunk) {
                    // Handle both array chunks (new) and string chunks (legacy/mock)
                    if (is_array($chunk)) {
                        $type = $chunk['type'] ?? 'text';
                        $content = $chunk['content'] ?? '';

                        if ($type === 'effort') {
                            echo 'data: '.json_encode(['effort' => $content])."\n\n";
                        } elseif ($type === 'context_tokens') {
                            echo 'data: '.json_encode(['context_tokens' => json_decode($content, true)])."\n\n";
                        } elseif ($type === 'status') {
                            echo 'data: '.json_encode(['status' => $content])."\n\n";
                        } elseif ($type === 'thinking') {
                            $thinkingContent .= $content;
                            echo 'data: '.json_encode(['thinking' => $content])."\n\n";
                        } elseif ($type === 'phase') {
                            echo 'data: '.json_encode(['phase' => $content])."\n\n";
                        } else {
                            $fullResponse .= $content;
                            echo 'data: '.json_encode(['text' => $content])."\n\n";
                        }
                    } else {
                        // Legacy string chunks (from mocks or simple stream)
                        $fullResponse .= $chunk;
                        echo 'data: '.json_encode(['text' => $chunk])."\n\n";
                    }

                    if (ob_get_level()) {
                        ob_flush();
                    }
                    flush();
                }
            } catch (\Throwable $e) {
                $fullResponse = 'I apologize, but I encountered an error processing your question. Please try again.';
                echo 'data: '.json_encode(['text' => $fullResponse, 'error' => true])."\n\n";
                if (ob_get_level()) {
                    ob_flush();
                }
                flush();

                \Illuminate\Support\Facades\Log::error('Chat AI error', [
                    'session_id' => $session->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Save the complete AI response with thinking metadata
            $messageData = [
                'session_id' => $session->id,
                'sender_type' => 'ai',
                'message_text' => $fullResponse,
                'ai_model_used' => app(AiTierManager::class)->current()->model(),
                'created_at' => now(),
            ];

            if ($thinkingContent) {
                $messageData['referenced_entities'] = [
                    'thinking' => mb_substr($thinkingContent, 0, 10000),
                ];
            }

            ChatMessage::create($messageData);

            // Signal that deep analysis is complete (for reveal transition)
            echo 'data: '.json_encode(['deep_ready' => true])."\n\n";
            echo "data: [DONE]\n\n";
            if (ob_get_level()) {
                ob_flush();
            }
            flush();
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
            'Content-Encoding' => 'none',
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
            ->get()
            ->map(fn ($msg) => [
                'id' => $msg->id,
                'role' => $msg->sender_type === 'patient' ? 'user' : 'assistant',
                'content' => $msg->message_text,
                'thinking' => $msg->referenced_entities['thinking'] ?? null,
                'created_at' => $msg->created_at,
            ]);

        return response()->json([
            'data' => [
                'session' => $session,
                'messages' => $messages,
            ],
        ]);
    }
}
