<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\Practitioner;
use App\Models\User;
use App\Models\Visit;
use App\Services\AI\AnthropicClient;
use Generator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorInquiryTest extends TestCase
{
    use RefreshDatabase;

    private User $doctorUser;

    private Practitioner $practitioner;

    private Organization $organization;

    private Patient $patient;

    private Visit $visit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->create();
        $this->practitioner = Practitioner::factory()->create(['organization_id' => $this->organization->id]);
        $this->doctorUser = User::factory()->doctor($this->practitioner)->create();
        $this->patient = Patient::factory()->create();
        $this->visit = Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'practitioner_id' => $this->practitioner->id,
            'organization_id' => $this->organization->id,
        ]);
    }

    public function test_doctor_can_inquire_about_patient_message(): void
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->doctorUser->id,
            'visit_id' => $this->visit->id,
            'type' => 'patient_feedback',
            'title' => 'Question',
            'body' => 'I have been feeling dizzy after taking my medication.',
        ]);

        $mockClient = $this->createMock(AnthropicClient::class);
        $mockClient->method('stream')
            ->willReturnCallback(function (): Generator {
                yield 'This is a test analysis.';
            });

        $this->app->instance(AnthropicClient::class, $mockClient);

        $response = $this->actingAs($this->doctorUser)
            ->post("/api/v1/doctor/messages/{$notification->id}/inquire");

        $response->assertOk();
        $this->assertStringStartsWith('text/event-stream', $response->headers->get('Content-Type'));
    }

    public function test_patient_cannot_access_inquire_endpoint(): void
    {
        $patientUser = User::factory()->patient($this->patient)->create();

        $notification = Notification::factory()->create([
            'user_id' => $this->doctorUser->id,
            'visit_id' => $this->visit->id,
            'type' => 'patient_feedback',
        ]);

        $response = $this->actingAs($patientUser)
            ->postJson("/api/v1/doctor/messages/{$notification->id}/inquire");

        $response->assertStatus(403);
    }

    public function test_conversation_thread_returns_patient_and_doctor_messages(): void
    {
        // Create patient message
        $patientUser = User::factory()->patient($this->patient)->create();

        Notification::factory()->create([
            'user_id' => $this->doctorUser->id,
            'visit_id' => $this->visit->id,
            'type' => 'patient_feedback',
            'title' => 'Question',
            'body' => 'How long should I take the medication?',
        ]);

        // Create doctor reply
        Notification::factory()->create([
            'user_id' => $patientUser->id,
            'visit_id' => $this->visit->id,
            'type' => 'doctor_reply',
            'title' => 'Reply from Dr. Smith',
            'body' => 'Please continue for 2 weeks.',
        ]);

        // Fetch as patient (via visit messages endpoint)
        $response = $this->actingAs($patientUser)
            ->getJson("/api/v1/visits/{$this->visit->id}/messages");

        $response->assertOk()
            ->assertJsonCount(2, 'data');

        $types = collect($response->json('data'))->pluck('type')->all();
        $this->assertContains('patient_feedback', $types);
        $this->assertContains('doctor_reply', $types);
    }

    public function test_unread_counts_included_in_patients_list(): void
    {
        // Create 2 unread patient messages
        Notification::factory()->count(2)->create([
            'user_id' => $this->doctorUser->id,
            'visit_id' => $this->visit->id,
            'type' => 'patient_feedback',
            'read_at' => null,
        ]);

        // Create 1 read patient message
        Notification::factory()->create([
            'user_id' => $this->doctorUser->id,
            'visit_id' => $this->visit->id,
            'type' => 'patient_feedback',
            'read_at' => now(),
        ]);

        $response = $this->actingAs($this->doctorUser)
            ->getJson('/api/v1/doctor/patients');

        $response->assertOk();
        $response->assertJsonPath('data.0.unread_count', 2);
    }
}
