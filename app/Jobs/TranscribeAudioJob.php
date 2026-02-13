<?php

namespace App\Jobs;

use App\Concerns\ResolvesAudioPaths;
use App\Models\Transcript;
use App\Services\AI\ScribeProcessor;
use App\Services\Stt\SpeechToTextProvider;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TranscribeAudioJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, ResolvesAudioPaths, SerializesModels;

    public int $tries = 2;

    public int $timeout = 600;

    public int $backoff = 60;

    public function __construct(
        public Transcript $transcript,
    ) {}

    public function handle(SpeechToTextProvider $stt): void
    {
        $disk = config('filesystems.upload');
        $chunkDir = "transcripts/{$this->transcript->visit_id}/chunks";

        try {
            $files = Storage::disk($disk)->files($chunkDir);

            // Sort by chunk index in filename (chunk-0.webm, chunk-1.webm, ...)
            usort($files, function (string $a, string $b): int {
                return $this->extractChunkIndex($a) <=> $this->extractChunkIndex($b);
            });

            if (empty($files)) {
                Log::error('TranscribeAudioJob: No chunk files found', [
                    'transcript_id' => $this->transcript->id,
                    'chunk_dir' => $chunkDir,
                ]);
                $this->transcript->update(['processing_status' => 'failed']);

                return;
            }

            $transcriptParts = [];

            foreach ($files as $file) {
                $absolutePath = $this->resolveLocalPath($disk, $file);

                try {
                    $text = $stt->transcribe($absolutePath);
                    $transcriptParts[] = $text;
                } finally {
                    $this->cleanupTempFile($disk, $absolutePath);
                }
            }

            $combinedTranscript = implode("\n\n", $transcriptParts);

            $quality = ScribeProcessor::evaluateQuality($combinedTranscript);

            // Update transcript with text and advance to processing — always proceed
            $this->transcript->update([
                'raw_transcript' => $combinedTranscript,
                'audio_file_path' => $files[0] ?? null,
                'processing_status' => 'processing',
            ]);

            if (! $quality['sufficient']) {
                Log::info('TranscribeAudioJob: Low quality transcript, proceeding anyway', [
                    'transcript_id' => $this->transcript->id,
                    'quality' => $quality,
                ]);
            }

            // Dispatch AI extraction (diarization, SOAP, terms)
            ProcessTranscriptJob::dispatch($this->transcript);

            Log::info('TranscribeAudioJob: Transcription complete, dispatched processing', [
                'transcript_id' => $this->transcript->id,
                'chunk_count' => count($files),
                'word_count' => $quality['word_count'],
            ]);
        } catch (\Throwable $e) {
            $this->transcript->update(['processing_status' => 'failed']);

            Log::error('TranscribeAudioJob failed', [
                'transcript_id' => $this->transcript->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function extractChunkIndex(string $path): int
    {
        $filename = basename($path);
        if (preg_match('/chunk-(\d+)/', $filename, $matches)) {
            return (int) $matches[1];
        }

        return 0;
    }
}
