<?php

namespace App\Jobs;

use App\Models\Document;
use App\Services\AI\DocumentAnalyzer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AnalyzeDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 600;

    public function __construct(
        public Document $document,
    ) {}

    public function handle(DocumentAnalyzer $analyzer): void
    {
        // Skip non-analyzable content types
        if (! in_array($this->document->content_type, ['image', 'pdf'])) {
            $this->document->update(['analysis_status' => 'skipped']);

            Log::info('Document analysis skipped (unsupported type)', [
                'document_id' => $this->document->id,
                'content_type' => $this->document->content_type,
            ]);

            return;
        }

        $this->document->update(['analysis_status' => 'processing']);

        try {
            $result = $analyzer->analyze($this->document);

            $this->document->update([
                'ai_analysis' => $result,
                'analysis_status' => 'completed',
                'analyzed_at' => now(),
            ]);

            Log::info('Document analyzed successfully', [
                'document_id' => $this->document->id,
                'category' => $result['document_category'] ?? 'unknown',
                'confidence' => $result['confidence'] ?? 'unknown',
            ]);
        } catch (\Throwable $e) {
            $this->document->update([
                'analysis_status' => 'failed',
                'analysis_error' => $e->getMessage(),
            ]);

            Log::error('Document analysis failed', [
                'document_id' => $this->document->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
