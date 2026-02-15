<?php

namespace App\Services\AI;

use App\Models\ChatSession;
use App\Models\PatientContextSummary;
use Illuminate\Support\Facades\Log;

class SessionSummarizer
{
    private const MIN_MESSAGES = 5;

    public function __construct(
        private AnthropicClient $client,
        private AiTierManager $tierManager,
    ) {}

    /**
     * Summarize a completed chat session into a structured context summary.
     *
     * Returns null if the session has fewer than MIN_MESSAGES messages,
     * or if summarization fails.
     */
    public function summarize(ChatSession $session): ?PatientContextSummary
    {
        $session->loadMissing(['messages', 'visit', 'patient']);

        if ($session->messages->count() < self::MIN_MESSAGES) {
            return null;
        }

        $visit = $session->visit;
        $patient = $session->patient;

        $visitReason = $visit?->reason_for_visit ?? 'Not specified';

        $formattedMessages = $session->messages
            ->sortBy('created_at')
            ->map(function ($msg) {
                $role = $msg->sender_type === 'patient' ? 'Patient' : 'AI';

                return "{$role}: {$msg->message_text}";
            })
            ->implode("\n");

        $prompt = <<<PROMPT
Analyze this patient chat session and generate a structured summary for future context.
The patient had a visit for: {$visitReason}

Chat messages:
{$formattedMessages}

Generate a JSON summary:
{
  "summary_text": "2-3 sentence narrative of the session",
  "key_questions": ["list of main questions the patient asked"],
  "concerns_raised": ["specific health concerns mentioned"],
  "followup_items": ["action items or things to monitor"],
  "emotional_context": "brief note on patient's emotional state"
}

Focus on clinically relevant information that would help the AI in future sessions with this patient.
Return ONLY the JSON object, no markdown fences or extra text.
PROMPT;

        try {
            $tier = $this->tierManager->current();

            $result = $this->client->chatWithThinking(
                'You are a clinical summarization assistant. Extract structured information from patient chat sessions.',
                [['role' => 'user', 'content' => $prompt]],
                [
                    'model' => $tier->model(),
                    'max_tokens' => 4000,
                    'budget_tokens' => 2000,
                ]
            );

            $parsed = AnthropicClient::parseJsonOutput($result['text'], []);

            if (empty($parsed) || empty($parsed['summary_text'])) {
                Log::warning('SessionSummarizer: AI returned invalid JSON', [
                    'session_id' => $session->id,
                    'response_preview' => substr($result['text'], 0, 200),
                ]);

                return null;
            }

            $summaryText = $parsed['summary_text'] ?? '';
            $tokenCount = (int) ceil(mb_strlen($summaryText) / 4);

            return PatientContextSummary::create([
                'patient_id' => $patient->id,
                'visit_id' => $visit?->id,
                'session_id' => $session->id,
                'summary_text' => $summaryText,
                'key_questions' => $parsed['key_questions'] ?? [],
                'concerns_raised' => $parsed['concerns_raised'] ?? [],
                'followup_items' => $parsed['followup_items'] ?? [],
                'emotional_context' => $parsed['emotional_context'] ?? null,
                'token_count' => $tokenCount,
            ]);
        } catch (\Throwable $e) {
            Log::error('SessionSummarizer: summarization failed', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
