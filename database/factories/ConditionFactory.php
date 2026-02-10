<?php

namespace Database\Factories;

use App\Models\Condition;
use App\Models\Patient;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ConditionFactory extends Factory
{
    protected $model = Condition::class;

    public function definition(): array
    {
        return [
            'fhir_condition_id' => 'Condition/' . Str::uuid(),
            'patient_id' => Patient::factory(),
            'visit_id' => Visit::factory(),
            'code_system' => 'ICD-10-CM',
            'code' => fake()->randomElement(['I49.3', 'I10', 'E11.9', 'J06.9']),
            'code_display' => fake()->randomElement(['Premature ventricular contractions', 'Essential hypertension', 'Type 2 diabetes', 'Upper respiratory infection']),
            'category' => 'encounter-diagnosis',
            'clinical_status' => 'active',
            'verification_status' => 'confirmed',
            'onset_date' => fake()->date(),
        ];
    }
}
