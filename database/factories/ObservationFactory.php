<?php

namespace Database\Factories;

use App\Models\Observation;
use App\Models\Patient;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ObservationFactory extends Factory
{
    protected $model = Observation::class;

    public function definition(): array
    {
        return [
            'fhir_observation_id' => 'Observation/' . Str::uuid(),
            'patient_id' => Patient::factory(),
            'visit_id' => Visit::factory(),
            'code_system' => 'LOINC',
            'code' => fake()->numerify('####-#'),
            'code_display' => fake()->randomElement(['Heart Rate', 'Blood Pressure', 'Cholesterol', 'TSH']),
            'category' => 'vital-signs',
            'status' => 'final',
            'value_type' => 'quantity',
            'value_quantity' => fake()->randomFloat(1, 60, 120),
            'value_unit' => 'bpm',
            'interpretation' => fake()->randomElement(['N', 'H', 'L']),
            'effective_date' => now()->toDateString(),
        ];
    }
}
