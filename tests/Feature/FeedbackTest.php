<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\Practitioner;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedbackTest extends TestCase
{
    use RefreshDatabase;

    private User $patientUser;
    private Visit $visit;

    protected function setUp(): void
    {
        parent::setUp();

        $org = Organization::factory()->create();
        $practitioner = Practitioner::factory()->create(['organization_id' => $org->id]);
        User::factory()->doctor($practitioner)->create();

        $patient = Patient::factory()->create();
        $this->patientUser = User::factory()->patient($patient)->create();
        $this->visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'practitioner_id' => $practitioner->id,
            'organization_id' => $org->id,
        ]);
    }

    public function test_patient_can_send_feedback(): void
    {
        $response = $this->actingAs($this->patientUser)->postJson("/api/v1/visits/{$this->visit->id}/messages", [
            'title' => 'Question about medication',
            'body' => 'I have a question about the dosage of my medication.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.type', 'patient_feedback');

        $this->assertDatabaseHas('notifications', [
            'type' => 'patient_feedback',
            'visit_id' => $this->visit->id,
        ]);
    }

    public function test_can_list_visit_messages(): void
    {
        Notification::factory()->count(2)->create([
            'visit_id' => $this->visit->id,
            'type' => 'patient_feedback',
        ]);

        Notification::factory()->create([
            'visit_id' => $this->visit->id,
            'type' => 'doctor_reply',
        ]);

        $response = $this->actingAs($this->patientUser)->getJson("/api/v1/visits/{$this->visit->id}/messages");

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_can_mark_message_as_read(): void
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->patientUser->id,
            'read_at' => null,
        ]);

        $response = $this->actingAs($this->patientUser)->patchJson("/api/v1/messages/{$notification->id}/read");

        $response->assertOk();
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_feedback_validates_input(): void
    {
        $response = $this->actingAs($this->patientUser)->postJson("/api/v1/visits/{$this->visit->id}/messages", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'body']);
    }
}
