<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\Transcript;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Factories\Factory;

class TranscriptFactory extends Factory
{
    protected $model = Transcript::class;

    public function definition(): array
    {
        return [
            'visit_id' => Visit::factory(),
            'patient_id' => Patient::factory(),
            'source_type' => 'manual_upload',
            'stt_provider' => 'whisper',
            'audio_duration_seconds' => fake()->numberBetween(60, 1800),
            'raw_transcript' => fake()->paragraphs(3, true),
            'processing_status' => 'completed',
            'patient_consent_given' => true,
            'consent_timestamp' => now(),
        ];
    }
}
