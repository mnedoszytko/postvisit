<?php

namespace Database\Factories;

use App\Models\ChatSession;
use App\Models\Patient;
use App\Models\PatientContextSummary;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Factories\Factory;

class PatientContextSummaryFactory extends Factory
{
    protected $model = PatientContextSummary::class;

    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'visit_id' => Visit::factory(),
            'session_id' => ChatSession::factory(),
            'summary_text' => fake()->paragraph(3),
            'key_questions' => [fake()->sentence(), fake()->sentence()],
            'concerns_raised' => [fake()->sentence()],
            'followup_items' => [fake()->sentence()],
            'emotional_context' => fake()->sentence(),
            'token_count' => fake()->numberBetween(100, 500),
        ];
    }
}
