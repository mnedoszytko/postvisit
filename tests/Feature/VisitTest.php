<?php

namespace Tests\Feature;

use App\Models\Condition;
use App\Models\Observation;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\Practitioner;
use App\Models\Prescription;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitNote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Patient $patient;
    private Practitioner $practitioner;
    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->create();
        $this->practitioner = Practitioner::factory()->create(['organization_id' => $this->organization->id]);
        $this->patient = Patient::factory()->create();
        $this->user = User::factory()->patient($this->patient)->create();
    }

    public function test_can_create_visit(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/visits', [
            'patient_id' => $this->patient->id,
            'practitioner_id' => $this->practitioner->id,
            'organization_id' => $this->organization->id,
            'visit_type' => 'office_visit',
            'reason_for_visit' => 'Annual checkup',
            'started_at' => now()->toISOString(),
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.visit_type', 'office_visit')
            ->assertJsonPath('data.visit_status', 'in_progress')
            ->assertJsonPath('data.reason_for_visit', 'Annual checkup');
    }

    public function test_can_view_visit_with_relationships(): void
    {
        $visit = Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'practitioner_id' => $this->practitioner->id,
            'organization_id' => $this->organization->id,
        ]);

        VisitNote::factory()->create([
            'visit_id' => $visit->id,
            'patient_id' => $this->patient->id,
            'author_practitioner_id' => $this->practitioner->id,
        ]);

        Observation::factory()->create([
            'patient_id' => $this->patient->id,
            'visit_id' => $visit->id,
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/visits/{$visit->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id', 'visit_type', 'reason_for_visit',
                    'patient', 'practitioner', 'organization',
                    'observations', 'conditions', 'prescriptions',
                    'visit_note',
                ],
            ]);
    }

    public function test_can_view_visit_summary(): void
    {
        $visit = Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'practitioner_id' => $this->practitioner->id,
            'organization_id' => $this->organization->id,
        ]);

        VisitNote::factory()->create([
            'visit_id' => $visit->id,
            'patient_id' => $this->patient->id,
            'author_practitioner_id' => $this->practitioner->id,
            'chief_complaint' => 'Headache',
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/visits/{$visit->id}/summary");

        $response->assertOk()
            ->assertJsonPath('data.visit_note.chief_complaint', 'Headache');
    }

    public function test_visit_requires_authentication(): void
    {
        $visit = Visit::factory()->create();

        $response = $this->getJson("/api/v1/visits/{$visit->id}");

        $response->assertStatus(401);
    }

    public function test_create_visit_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/visits', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['patient_id', 'practitioner_id', 'visit_type', 'reason_for_visit', 'started_at']);
    }
}
