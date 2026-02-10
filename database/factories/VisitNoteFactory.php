<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\Practitioner;
use App\Models\Visit;
use App\Models\VisitNote;
use Illuminate\Database\Eloquent\Factories\Factory;

class VisitNoteFactory extends Factory
{
    protected $model = VisitNote::class;

    public function definition(): array
    {
        return [
            'visit_id' => Visit::factory(),
            'patient_id' => Patient::factory(),
            'author_practitioner_id' => Practitioner::factory(),
            'composition_type' => 'progress_note',
            'status' => 'final',
            'chief_complaint' => fake()->sentence(),
            'history_of_present_illness' => fake()->paragraph(),
            'review_of_systems' => fake()->paragraph(),
            'physical_exam' => fake()->paragraph(),
            'assessment' => fake()->paragraph(),
            'plan' => fake()->paragraph(),
            'follow_up' => fake()->sentence(),
            'follow_up_timeframe' => '2 weeks',
        ];
    }
}
