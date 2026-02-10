<?php

namespace Database\Factories;

use App\Models\Medication;
use App\Models\MedicationInteraction;
use Illuminate\Database\Eloquent\Factories\Factory;

class MedicationInteractionFactory extends Factory
{
    protected $model = MedicationInteraction::class;

    public function definition(): array
    {
        return [
            'drug_a_id' => Medication::factory(),
            'drug_b_id' => Medication::factory(),
            'severity' => fake()->randomElement(['minor', 'moderate', 'major']),
            'description' => fake()->sentence(),
            'management' => fake()->sentence(),
            'source_database' => 'drugbank',
            'should_alert' => true,
            'created_at' => now(),
        ];
    }
}
