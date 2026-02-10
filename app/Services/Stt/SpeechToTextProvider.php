<?php

namespace App\Services\Stt;

interface SpeechToTextProvider
{
    /**
     * Transcribe an audio file to text.
     *
     * @param string $audioPath Absolute path to the audio file
     * @return string The transcribed text
     */
    public function transcribe(string $audioPath): string;

    /**
     * Get the list of supported audio formats.
     *
     * @return array<string> E.g., ['mp3', 'wav', 'webm', 'm4a']
     */
    public function getSupportedFormats(): array;
}
