<?php

namespace Database\Factories;

use App\Models\Medication;
use App\Models\Patient;
use App\Models\Practitioner;
use App\Models\Prescription;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PrescriptionFactory extends Factory
{
    protected $model = Prescription::class;

    public function definition(): array
    {
        return [
            'fhir_medication_request_id' => 'MedicationRequest/' . Str::uuid(),
            'patient_id' => Patient::factory(),
            'practitioner_id' => Practitioner::factory(),
            'visit_id' => Visit::factory(),
            'medication_id' => Medication::factory(),
            'status' => 'active',
            'intent' => 'order',
            'dose_quantity' => fake()->randomElement([10, 20, 40]),
            'dose_unit' => 'mg',
            'frequency' => 'BID',
            'frequency_text' => 'Twice daily',
            'route' => 'oral',
            'start_date' => now()->toDateString(),
            'number_of_refills' => 3,
            'refills_remaining' => 3,
            'substitution_allowed' => true,
        ];
    }
}
