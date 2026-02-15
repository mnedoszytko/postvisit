<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Practitioner;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PractitionerFactory extends Factory
{
    protected $model = Practitioner::class;

    public function definition(): array
    {
        return [
            'fhir_practitioner_id' => 'Practitioner/'.Str::uuid(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'npi' => fake()->unique()->numerify('##########'),
            'license_number' => 'LIC-'.fake()->numerify('######'),
            'medical_degree' => fake()->randomElement(['MD', 'DO']),
            'primary_specialty' => fake()->randomElement(['cardiology', 'internal_medicine', 'family_medicine']),
            'organization_id' => Organization::factory(),
        ];
    }
}
