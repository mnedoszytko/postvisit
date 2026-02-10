<?php

namespace Database\Factories;

use App\Models\Consent;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConsentFactory extends Factory
{
    protected $model = Consent::class;

    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'consent_type' => fake()->randomElement(['privacy', 'data_sharing', 'recording']),
            'status' => 'active',
            'consented_at' => now(),
            'version' => '1.0',
        ];
    }
}
