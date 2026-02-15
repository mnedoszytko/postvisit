<?php

namespace App\Jobs;

use App\Models\ChatSession;
use App\Services\AI\SessionSummarizer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SummarizeChatSession implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct(
        public ChatSession $session,
    ) {}

    public function handle(SessionSummarizer $summarizer): void
    {
        try {
            $summary = $summarizer->summarize($this->session);

            if ($summary) {
                Log::info('Chat session summarized successfully', [
                    'session_id' => $this->session->id,
                    'summary_id' => $summary->id,
                    'token_count' => $summary->token_count,
                ]);
            } else {
                Log::info('Chat session skipped (too few messages or parse failure)', [
                    'session_id' => $this->session->id,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Chat session summarization job failed', [
                'session_id' => $this->session->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
