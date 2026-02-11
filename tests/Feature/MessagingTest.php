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

class MessagingTest extends TestCase
{
    use RefreshDatabase;

    private User $patientUser;

    private User $doctorUser;

    private Visit $visit;

    protected function setUp(): void
    {
        parent::setUp();

        $org = Organization::factory()->create();
        $practitioner = Practitioner::factory()->create(['organization_id' => $org->id]);
        $this->doctorUser = User::factory()->doctor($practitioner)->create();

        $patient = Patient::factory()->create();
        $this->patientUser = User::factory()->patient($patient)->create();
        $this->visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'practitioner_id' => $practitioner->id,
            'organization_id' => $org->id,
        ]);
    }

    public function test_end_to_end_patient_doctor_messaging(): void
    {
        // 1. Patient sends a message to doctor
        $sendResponse = $this->actingAs($this->patientUser)
            ->postJson("/api/v1/visits/{$this->visit->id}/messages", [
                'title' => 'Side effect concern',
                'body' => 'I feel dizzy after taking the medication.',
            ]);

        $sendResponse->assertStatus(201)
            ->assertJsonPath('data.type', 'patient_feedback');

        $messageId = $sendResponse->json('data.id');

        // 2. Doctor sees the message in notifications
        $notifResponse = $this->actingAs($this->doctorUser)
            ->getJson('/api/v1/doctor/notifications');

        $notifResponse->assertOk();
        $notifications = collect($notifResponse->json('data'));
        $this->assertTrue(
            $notifications->contains('id', $messageId),
            'Doctor should see the patient message in notifications'
        );

        // 3. Doctor replies to the message
        $replyResponse = $this->actingAs($this->doctorUser)
            ->postJson("/api/v1/doctor/messages/{$messageId}/reply", [
                'body' => 'Please reduce the dose and monitor. Call if symptoms persist.',
            ]);

        $replyResponse->assertStatus(201)
            ->assertJsonPath('data.type', 'doctor_reply');

        // 4. Patient sees both the original message and the reply in visit messages
        $messagesResponse = $this->actingAs($this->patientUser)
            ->getJson("/api/v1/visits/{$this->visit->id}/messages");

        $messagesResponse->assertOk()
            ->assertJsonCount(2, 'data');

        $types = collect($messagesResponse->json('data'))->pluck('type')->toArray();
        $this->assertContains('patient_feedback', $types);
        $this->assertContains('doctor_reply', $types);
    }

    public function test_doctor_reply_links_to_original_message(): void
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->doctorUser->id,
            'visit_id' => $this->visit->id,
            'type' => 'patient_feedback',
            'title' => 'Question',
            'body' => 'How long should I take this?',
        ]);

        $response = $this->actingAs($this->doctorUser)
            ->postJson("/api/v1/doctor/messages/{$notification->id}/reply", [
                'body' => 'Continue for 30 days as prescribed.',
            ]);

        $response->assertStatus(201);
        $replyData = $response->json('data');

        $this->assertEquals('doctor_reply', $replyData['type']);
        $this->assertEquals($notification->id, $replyData['data']['original_notification_id']);
    }

    public function test_message_can_be_marked_as_read(): void
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->patientUser->id,
            'visit_id' => $this->visit->id,
            'type' => 'doctor_reply',
            'read_at' => null,
        ]);

        $this->assertNull($notification->read_at);

        $response = $this->actingAs($this->patientUser)
            ->patchJson("/api/v1/messages/{$notification->id}/read");

        $response->assertOk();
        $this->assertNotNull($notification->fresh()->read_at);
    }
}
