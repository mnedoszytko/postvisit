<?php

namespace Tests\Feature;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\Practitioner;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Visit $visit;

    protected function setUp(): void
    {
        parent::setUp();

        $org = Organization::factory()->create();
        $practitioner = Practitioner::factory()->create(['organization_id' => $org->id]);
        $patient = Patient::factory()->create();
        $this->user = User::factory()->patient($patient)->create();
        $this->visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'practitioner_id' => $practitioner->id,
            'organization_id' => $org->id,
        ]);
    }

    public function test_can_send_message(): void
    {
        $response = $this->actingAs($this->user)->postJson("/api/v1/visits/{$this->visit->id}/chat", [
            'message' => 'What are the side effects of my medication?',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['user_message', 'ai_message', 'session_id'],
            ]);

        $this->assertDatabaseHas('chat_messages', [
            'sender_type' => 'patient',
            'message_text' => 'What are the side effects of my medication?',
        ]);

        $this->assertDatabaseHas('chat_messages', [
            'sender_type' => 'ai',
        ]);
    }

    public function test_send_message_creates_session_if_not_exists(): void
    {
        $this->assertDatabaseCount('chat_sessions', 0);

        $this->actingAs($this->user)->postJson("/api/v1/visits/{$this->visit->id}/chat", [
            'message' => 'Hello',
        ]);

        $this->assertDatabaseCount('chat_sessions', 1);
        $this->assertDatabaseHas('chat_sessions', [
            'visit_id' => $this->visit->id,
            'patient_id' => $this->visit->patient_id,
        ]);
    }

    public function test_send_message_reuses_existing_session(): void
    {
        ChatSession::factory()->create([
            'visit_id' => $this->visit->id,
            'patient_id' => $this->visit->patient_id,
        ]);

        $this->actingAs($this->user)->postJson("/api/v1/visits/{$this->visit->id}/chat", [
            'message' => 'Follow-up question',
        ]);

        $this->assertDatabaseCount('chat_sessions', 1);
    }

    public function test_can_get_chat_history(): void
    {
        $session = ChatSession::factory()->create([
            'visit_id' => $this->visit->id,
            'patient_id' => $this->visit->patient_id,
        ]);

        ChatMessage::factory()->count(5)->create(['session_id' => $session->id]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/visits/{$this->visit->id}/chat/history");

        $response->assertOk()
            ->assertJsonCount(5, 'data.messages')
            ->assertJsonPath('data.session.id', $session->id);
    }

    public function test_chat_history_returns_empty_when_no_session(): void
    {
        $response = $this->actingAs($this->user)->getJson("/api/v1/visits/{$this->visit->id}/chat/history");

        $response->assertOk()
            ->assertJsonPath('data.session', null)
            ->assertJsonPath('data.messages', []);
    }

    public function test_send_message_validates_input(): void
    {
        $response = $this->actingAs($this->user)->postJson("/api/v1/visits/{$this->visit->id}/chat", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('message');
    }

    public function test_chat_requires_authentication(): void
    {
        $response = $this->postJson("/api/v1/visits/{$this->visit->id}/chat", [
            'message' => 'Hello',
        ]);

        $response->assertStatus(401);
    }
}
