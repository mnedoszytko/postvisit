<?php

namespace Tests\Feature;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\Notification;
use App\Models\Observation;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\Practitioner;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorTest extends TestCase
{
    use RefreshDatabase;

    private User $doctorUser;

    private Practitioner $practitioner;

    private Organization $organization;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->create();
        $this->practitioner = Practitioner::factory()->create(['organization_id' => $this->organization->id]);
        $this->doctorUser = User::factory()->doctor($this->practitioner)->create();
        $this->patient = Patient::factory()->create();
    }

    public function test_doctor_can_view_dashboard(): void
    {
        Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'practitioner_id' => $this->practitioner->id,
            'organization_id' => $this->organization->id,
        ]);

        $response = $this->actingAs($this->doctorUser)->getJson('/api/v1/doctor/dashboard');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'recent_visits',
                    'stats' => ['total_patients', 'total_visits', 'unread_notifications'],
                ],
            ]);
    }

    public function test_doctor_can_list_patients(): void
    {
        Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'practitioner_id' => $this->practitioner->id,
            'organization_id' => $this->organization->id,
        ]);

        $response = $this->actingAs($this->doctorUser)->getJson('/api/v1/doctor/patients');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_doctor_can_search_patients_by_name(): void
    {
        $patient2 = Patient::factory()->create(['first_name' => 'Maria', 'last_name' => 'Santos']);
        $patient3 = Patient::factory()->create(['first_name' => 'James', 'last_name' => 'Williams']);

        Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'practitioner_id' => $this->practitioner->id,
            'organization_id' => $this->organization->id,
        ]);
        Visit::factory()->create([
            'patient_id' => $patient2->id,
            'practitioner_id' => $this->practitioner->id,
            'organization_id' => $this->organization->id,
        ]);
        Visit::factory()->create([
            'patient_id' => $patient3->id,
            'practitioner_id' => $this->practitioner->id,
            'organization_id' => $this->organization->id,
        ]);

        // Search by first name (case-insensitive)
        $response = $this->actingAs($this->doctorUser)->getJson('/api/v1/doctor/patients?search=maria');
        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.first_name', 'Maria');

        // Search by last name (case-insensitive)
        $response = $this->actingAs($this->doctorUser)->getJson('/api/v1/doctor/patients?search=Williams');
        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.last_name', 'Williams');

        // Search with no match returns empty
        $response = $this->actingAs($this->doctorUser)->getJson('/api/v1/doctor/patients?search=nonexistent');
        $response->assertOk()
            ->assertJsonCount(0, 'data');

        // Empty search returns all patients
        $response = $this->actingAs($this->doctorUser)->getJson('/api/v1/doctor/patients');
        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_doctor_can_view_patient_detail(): void
    {
        Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'practitioner_id' => $this->practitioner->id,
            'organization_id' => $this->organization->id,
        ]);

        $response = $this->actingAs($this->doctorUser)->getJson("/api/v1/doctor/patients/{$this->patient->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $this->patient->id);
    }

    public function test_doctor_can_view_patient_visits(): void
    {
        Visit::factory()->count(2)->create([
            'patient_id' => $this->patient->id,
            'practitioner_id' => $this->practitioner->id,
            'organization_id' => $this->organization->id,
        ]);

        $response = $this->actingAs($this->doctorUser)->getJson("/api/v1/doctor/patients/{$this->patient->id}/visits");

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_doctor_can_view_engagement(): void
    {
        $visit = Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'practitioner_id' => $this->practitioner->id,
            'organization_id' => $this->organization->id,
        ]);

        $session = ChatSession::factory()->create([
            'patient_id' => $this->patient->id,
            'visit_id' => $visit->id,
        ]);

        ChatMessage::factory()->count(3)->create(['session_id' => $session->id]);

        $response = $this->actingAs($this->doctorUser)->getJson("/api/v1/doctor/patients/{$this->patient->id}/engagement");

        $response->assertOk()
            ->assertJsonPath('data.total_sessions', 1)
            ->assertJsonPath('data.total_messages', 3);
    }

    public function test_doctor_can_view_chat_audit(): void
    {
        $visit = Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'practitioner_id' => $this->practitioner->id,
            'organization_id' => $this->organization->id,
        ]);

        $session = ChatSession::factory()->create([
            'patient_id' => $this->patient->id,
            'visit_id' => $visit->id,
        ]);

        ChatMessage::factory()->count(2)->create(['session_id' => $session->id]);

        $response = $this->actingAs($this->doctorUser)->getJson("/api/v1/doctor/patients/{$this->patient->id}/chat-audit");

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_doctor_can_view_notifications(): void
    {
        Notification::factory()->count(3)->create(['user_id' => $this->doctorUser->id]);

        $response = $this->actingAs($this->doctorUser)->getJson('/api/v1/doctor/notifications');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_doctor_can_reply_to_notification(): void
    {
        $patientUser = User::factory()->patient($this->patient)->create();
        $visit = Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'practitioner_id' => $this->practitioner->id,
            'organization_id' => $this->organization->id,
        ]);

        $notification = Notification::factory()->create([
            // Patient feedback is addressed to the practitioner user.
            'user_id' => $this->doctorUser->id,
            'visit_id' => $visit->id,
            'type' => 'patient_feedback',
            'data' => [
                'from_user_id' => $patientUser->id,
                'from_name' => $patientUser->name,
            ],
        ]);

        $response = $this->actingAs($this->doctorUser)->postJson("/api/v1/doctor/messages/{$notification->id}/reply", [
            'body' => 'Thank you for your feedback.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.type', 'doctor_reply');
    }

    public function test_patient_cannot_access_doctor_dashboard(): void
    {
        $patientUser = User::factory()->patient($this->patient)->create();

        $response = $this->actingAs($patientUser)->getJson('/api/v1/doctor/dashboard');

        $response->assertStatus(403);
    }

    public function test_unauthenticated_cannot_access_doctor_routes(): void
    {
        $response = $this->getJson('/api/v1/doctor/dashboard');

        $response->assertStatus(401);
    }

    public function test_doctor_can_view_patient_observations(): void
    {
        $visit = Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'practitioner_id' => $this->practitioner->id,
            'organization_id' => $this->organization->id,
        ]);

        Observation::factory()->count(3)->create([
            'patient_id' => $this->patient->id,
            'visit_id' => $visit->id,
        ]);

        $response = $this->actingAs($this->doctorUser)
            ->getJson("/api/v1/doctor/patients/{$this->patient->id}/observations");

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_doctor_can_filter_observations_by_code(): void
    {
        $visit = Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'practitioner_id' => $this->practitioner->id,
            'organization_id' => $this->organization->id,
        ]);

        Observation::factory()->create([
            'patient_id' => $this->patient->id,
            'visit_id' => $visit->id,
            'code' => '29463-7',
            'code_display' => 'Body weight',
        ]);

        Observation::factory()->create([
            'patient_id' => $this->patient->id,
            'visit_id' => $visit->id,
            'code' => '8867-4',
            'code_display' => 'Heart rate',
        ]);

        $response = $this->actingAs($this->doctorUser)
            ->getJson("/api/v1/doctor/patients/{$this->patient->id}/observations?code=29463-7");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.code', '29463-7');
    }

    public function test_doctor_can_filter_observations_by_category(): void
    {
        $visit = Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'practitioner_id' => $this->practitioner->id,
            'organization_id' => $this->organization->id,
        ]);

        Observation::factory()->create([
            'patient_id' => $this->patient->id,
            'visit_id' => $visit->id,
            'category' => 'vital-signs',
        ]);

        Observation::factory()->create([
            'patient_id' => $this->patient->id,
            'visit_id' => $visit->id,
            'category' => 'laboratory',
        ]);

        $response = $this->actingAs($this->doctorUser)
            ->getJson("/api/v1/doctor/patients/{$this->patient->id}/observations?category=laboratory");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.category', 'laboratory');
    }

    public function test_weight_observations_include_specialty_data(): void
    {
        $visit = Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'practitioner_id' => $this->practitioner->id,
            'organization_id' => $this->organization->id,
        ]);

        Observation::factory()->create([
            'patient_id' => $this->patient->id,
            'visit_id' => $visit->id,
            'code' => '29463-7',
            'code_display' => 'Body weight',
            'value_type' => 'quantity',
            'value_quantity' => 85.3,
            'value_unit' => 'kg',
            'specialty_data' => [
                'monitoring_context' => 'heart_failure',
                'dry_weight' => 82.0,
                'alert_threshold_kg' => 2.0,
                'alert_threshold_days' => 3,
            ],
        ]);

        $response = $this->actingAs($this->doctorUser)
            ->getJson("/api/v1/doctor/patients/{$this->patient->id}/observations?code=29463-7");

        $response->assertOk()
            ->assertJsonPath('data.0.specialty_data.monitoring_context', 'heart_failure')
            ->assertJsonPath('data.0.specialty_data.dry_weight', 82);
    }

    public function test_patient_cannot_access_doctor_observations(): void
    {
        $patientUser = User::factory()->patient($this->patient)->create();

        $response = $this->actingAs($patientUser)
            ->getJson("/api/v1/doctor/patients/{$this->patient->id}/observations");

        $response->assertStatus(403);
    }
}
