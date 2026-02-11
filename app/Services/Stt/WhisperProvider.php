<?php

namespace App\Services\Stt;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class WhisperProvider implements SpeechToTextProvider
{
    private string $apiKey;
    private string $apiUrl;
    private string $model;

    public function __construct()
    {
        $this->apiKey = config('services.stt.whisper_api_key', env('OPENAI_API_KEY', ''));
        $this->apiUrl = config('services.stt.whisper_api_url', 'https://api.openai.com/v1/audio/transcriptions');
        $this->model = config('services.stt.whisper_model', 'whisper-1');
    }

    /**
     * Transcribe an audio file using OpenAI Whisper API.
     */
    public function transcribe(string $audioPath): string
    {
        if (! file_exists($audioPath)) {
            throw new RuntimeException("Audio file not found: {$audioPath}");
        }

        $response = Http::timeout(300)
            ->withToken($this->apiKey)
            ->attach('file', file_get_contents($audioPath), basename($audioPath))
            ->post($this->apiUrl, [
                'model' => $this->model,
                'language' => 'en',
                'response_format' => 'text',
            ]);

        if (! $response->successful()) {
            Log::error('Whisper transcription failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException('Whisper transcription failed: ' . $response->body());
        }

        return trim($response->body());
    }

    /**
     * Supported audio formats for Whisper API.
     */
    public function getSupportedFormats(): array
    {
        return ['mp3', 'mp4', 'mpeg', 'mpga', 'm4a', 'wav', 'webm'];
    }
}
