<?php

namespace Tests\Feature;

use App\Models\Medication;
use App\Models\MedicationInteraction;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\Practitioner;
use App\Models\Prescription;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrescriptionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Patient $patient;
    private Visit $visit;
    private Practitioner $practitioner;
    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->create();
        $this->practitioner = Practitioner::factory()->create(['organization_id' => $this->organization->id]);
        $this->patient = Patient::factory()->create();
        $this->user = User::factory()->patient($this->patient)->create();
        $this->visit = Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'practitioner_id' => $this->practitioner->id,
            'organization_id' => $this->organization->id,
        ]);
    }

    public function test_can_list_visit_prescriptions(): void
    {
        Prescription::factory()->count(2)->create([
            'patient_id' => $this->patient->id,
            'practitioner_id' => $this->practitioner->id,
            'visit_id' => $this->visit->id,
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/visits/{$this->visit->id}/prescriptions");

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_can_list_patient_prescriptions(): void
    {
        Prescription::factory()->count(3)->create([
            'patient_id' => $this->patient->id,
            'practitioner_id' => $this->practitioner->id,
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/patients/{$this->patient->id}/prescriptions");

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_can_check_patient_interactions(): void
    {
        $medA = Medication::factory()->create();
        $medB = Medication::factory()->create();

        Prescription::factory()->create([
            'patient_id' => $this->patient->id,
            'practitioner_id' => $this->practitioner->id,
            'medication_id' => $medA->id,
            'status' => 'active',
        ]);

        Prescription::factory()->create([
            'patient_id' => $this->patient->id,
            'practitioner_id' => $this->practitioner->id,
            'medication_id' => $medB->id,
            'status' => 'active',
        ]);

        MedicationInteraction::factory()->create([
            'drug_a_id' => $medA->id,
            'drug_b_id' => $medB->id,
            'severity' => 'major',
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/patients/{$this->patient->id}/prescriptions/interactions");

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_no_interactions_when_single_medication(): void
    {
        $med = Medication::factory()->create();

        Prescription::factory()->create([
            'patient_id' => $this->patient->id,
            'practitioner_id' => $this->practitioner->id,
            'medication_id' => $med->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/patients/{$this->patient->id}/prescriptions/interactions");

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
