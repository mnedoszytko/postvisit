<?php

namespace Tests\Feature;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\Notification;
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

    public function test_doctor_can_view_patient_detail(): void
    {
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
        $notification = Notification::factory()->create([
            'user_id' => $patientUser->id,
            'type' => 'patient_feedback',
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
}
