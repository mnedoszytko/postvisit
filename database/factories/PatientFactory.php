<?php

namespace Database\Factories;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PatientFactory extends Factory
{
    protected $model = Patient::class;

    public function definition(): array
    {
        return [
            'fhir_patient_id' => 'Patient/'.Str::uuid(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'dob' => fake()->date('Y-m-d', '-18 years'),
            'gender' => fake()->randomElement(['male', 'female', 'other']),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'preferred_language' => 'en',
            'timezone' => 'UTC',
            'mrn' => 'MRN-'.fake()->unique()->numerify('######'),
            'consent_given' => true,
            'consent_date' => now(),
            'data_sharing_consent' => true,
        ];
    }

    public function withoutConsent(): static
    {
        return $this->state(fn () => [
            'consent_given' => false,
            'consent_date' => null,
            'data_sharing_consent' => false,
        ]);
    }
}
