<?php

namespace Tests\Feature;

use App\Models\Condition;
use App\Models\Document;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\Practitioner;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->patient = Patient::factory()->create();
        $this->user = User::factory()->patient($this->patient)->create();
    }

    public function test_can_view_patient_profile(): void
    {
        $response = $this->actingAs($this->user)->getJson("/api/v1/patients/{$this->patient->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $this->patient->id)
            ->assertJsonPath('data.first_name', $this->patient->first_name);
    }

    public function test_patient_profile_includes_conditions_and_prescriptions(): void
    {
        Condition::factory()->create(['patient_id' => $this->patient->id]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/patients/{$this->patient->id}");

        $response->assertOk()
            ->assertJsonStructure(['data' => ['conditions', 'prescriptions']]);
    }

    public function test_can_list_patient_visits(): void
    {
        $org = Organization::factory()->create();
        $practitioner = Practitioner::factory()->create(['organization_id' => $org->id]);

        Visit::factory()->count(3)->create([
            'patient_id' => $this->patient->id,
            'practitioner_id' => $practitioner->id,
            'organization_id' => $org->id,
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/patients/{$this->patient->id}/visits");

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_can_list_patient_conditions(): void
    {
        Condition::factory()->count(2)->create(['patient_id' => $this->patient->id]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/patients/{$this->patient->id}/conditions");

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_can_view_health_record(): void
    {
        $response = $this->actingAs($this->user)->getJson("/api/v1/patients/{$this->patient->id}/health-record");

        $response->assertOk()
            ->assertJsonStructure(['data' => ['conditions', 'prescriptions', 'visits']]);
    }

    public function test_can_list_patient_documents(): void
    {
        Document::factory()->count(2)->create(['patient_id' => $this->patient->id]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/patients/{$this->patient->id}/documents");

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_patient_requires_authentication(): void
    {
        $response = $this->getJson("/api/v1/patients/{$this->patient->id}");

        $response->assertStatus(401);
    }
}
