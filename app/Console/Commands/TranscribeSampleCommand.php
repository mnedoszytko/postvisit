<?php

namespace App\Console\Commands;

use App\Services\Stt\WhisperProvider;
use Illuminate\Console\Command;

class TranscribeSampleCommand extends Command
{
    protected $signature = 'app:transcribe-sample';

    protected $description = 'Transcribe sample audio file using OpenAI Whisper API';

    public function handle(): int
    {
        $audioPath = base_path('samples/sample-visit-audio.m4a');

        if (! file_exists($audioPath)) {
            $this->error("Audio file not found: {$audioPath}");
            return self::FAILURE;
        }

        $sizeMb = round(filesize($audioPath) / 1024 / 1024, 1);
        $this->info("Transcribing {$audioPath} ({$sizeMb} MB)...");
        $this->info('This may take a few minutes for large files.');

        try {
            $whisper = new WhisperProvider();
            $transcript = $whisper->transcribe($audioPath);
        } catch (\Throwable $e) {
            $this->error("Transcription failed: {$e->getMessage()}");
            return self::FAILURE;
        }

        if (empty($transcript)) {
            $this->error('Transcription returned empty result.');
            return self::FAILURE;
        }

        $outputPath = base_path('samples/sample-transcript.txt');
        file_put_contents($outputPath, $transcript);

        $wordCount = str_word_count($transcript);
        $this->info("Transcription successful!");
        $this->info("Word count: {$wordCount}");
        $this->info("Saved to: {$outputPath}");

        return self::SUCCESS;
    }
}
