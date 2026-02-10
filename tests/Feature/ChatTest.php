<?php

namespace Tests\Feature;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\Practitioner;
use App\Models\User;
use App\Models\Visit;
use App\Services\AI\QaAssistant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
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

    private function mockQaAssistant(string $responseText = 'Test AI response.'): void
    {
        $this->mock(QaAssistant::class, function (MockInterface $mock) use ($responseText) {
            $mock->shouldReceive('answer')
                ->andReturnUsing(function () use ($responseText) {
                    yield $responseText;
                });
        });
    }

    public function test_can_send_message(): void
    {
        $this->mockQaAssistant('Hello, this is a test response.');

        $response = $this->actingAs($this->user)->postJson(
            "/api/v1/visits/{$this->visit->id}/chat",
            ['message' => 'What are the side effects of my medication?']
        );

        $response->assertOk();
        $this->assertStringStartsWith('text/event-stream', $response->headers->get('Content-Type'));

        // Execute the streamed response to trigger the callback
        // (saves AI message to DB and outputs SSE)
        ob_start();
        $response->baseResponse->sendContent();
        ob_end_clean();

        // Verify database records
        $this->assertDatabaseHas('chat_messages', [
            'sender_type' => 'patient',
            'message_text' => 'What are the side effects of my medication?',
        ]);

        $this->assertDatabaseHas('chat_messages', [
            'sender_type' => 'ai',
            'message_text' => 'Hello, this is a test response.',
        ]);
    }

    public function test_send_message_creates_session_if_not_exists(): void
    {
        $this->mockQaAssistant();
        $this->assertDatabaseCount('chat_sessions', 0);

        $response = $this->actingAs($this->user)->postJson(
            "/api/v1/visits/{$this->visit->id}/chat",
            ['message' => 'Hello']
        );

        // Execute the stream to complete the request
        ob_start();
        $response->baseResponse->sendContent();
        ob_get_clean();

        $this->assertDatabaseCount('chat_sessions', 1);
        $this->assertDatabaseHas('chat_sessions', [
            'visit_id' => $this->visit->id,
            'patient_id' => $this->visit->patient_id,
        ]);
    }

    public function test_send_message_reuses_existing_session(): void
    {
        $this->mockQaAssistant();

        ChatSession::factory()->create([
            'visit_id' => $this->visit->id,
            'patient_id' => $this->visit->patient_id,
        ]);

        $response = $this->actingAs($this->user)->postJson(
            "/api/v1/visits/{$this->visit->id}/chat",
            ['message' => 'Follow-up question']
        );

        // Execute the stream to complete the request
        ob_start();
        $response->baseResponse->sendContent();
        ob_get_clean();

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
