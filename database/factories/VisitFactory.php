<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Patient;
use App\Models\Practitioner;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class VisitFactory extends Factory
{
    protected $model = Visit::class;

    public function definition(): array
    {
        $started = fake()->dateTimeBetween('-30 days', 'now');

        return [
            'fhir_encounter_id' => 'Encounter/' . Str::uuid(),
            'patient_id' => Patient::factory(),
            'practitioner_id' => Practitioner::factory(),
            'organization_id' => Organization::factory(),
            'visit_type' => fake()->randomElement(['office_visit', 'telehealth']),
            'class' => 'AMB',
            'visit_status' => 'completed',
            'reason_for_visit' => fake()->sentence(),
            'started_at' => $started,
            'ended_at' => (clone $started)->modify('+30 minutes'),
            'duration_minutes' => 30,
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn () => [
            'visit_status' => 'in_progress',
            'ended_at' => null,
            'duration_minutes' => null,
        ]);
    }
}
