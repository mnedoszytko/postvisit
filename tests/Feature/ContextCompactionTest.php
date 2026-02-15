<?php

namespace Tests\Feature;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\PatientContextSummary;
use App\Models\Practitioner;
use App\Models\User;
use App\Models\Visit;
use App\Services\AI\AnthropicClient;
use App\Services\AI\ContextAssembler;
use App\Services\AI\SessionSummarizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class ContextCompactionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Patient $patient;

    private Visit $visit;

    protected function setUp(): void
    {
        parent::setUp();

        $org = Organization::factory()->create();
        $practitioner = Practitioner::factory()->create(['organization_id' => $org->id]);
        $this->patient = Patient::factory()->create();
        $this->user = User::factory()->patient($this->patient)->create();
        $this->visit = Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'practitioner_id' => $practitioner->id,
            'organization_id' => $org->id,
        ]);
    }

    public function test_context_compaction_layer_returns_null_when_disabled(): void
    {
        // Default config has context_compaction_enabled = false
        config(['postvisit.context_compaction_enabled' => false]);

        // Create summaries that should NOT be loaded
        PatientContextSummary::factory()->create([
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
        ]);

        $assembler = app(ContextAssembler::class);
        $result = $assembler->assembleForVisit($this->visit);

        // The context should NOT contain previous session context
        $allContent = collect($result['context_messages'])
            ->pluck('content')
            ->implode("\n");

        $this->assertStringNotContainsString('PREVIOUS SESSION CONTEXT', $allContent);
    }

    public function test_context_compaction_layer_loads_summaries_when_enabled(): void
    {
        config(['postvisit.context_compaction_enabled' => true]);

        PatientContextSummary::factory()->create([
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
            'summary_text' => 'Patient asked about medication side effects and follow-up timing.',
            'key_questions' => ['What are the side effects?', 'When is my follow-up?'],
            'concerns_raised' => ['Worried about dizziness'],
            'followup_items' => ['Schedule follow-up in 2 weeks'],
            'emotional_context' => 'Anxious but engaged',
        ]);

        $assembler = app(ContextAssembler::class);
        $result = $assembler->assembleForVisit($this->visit);

        $allContent = collect($result['context_messages'])
            ->pluck('content')
            ->implode("\n");

        $this->assertStringContainsString('PREVIOUS SESSION CONTEXT', $allContent);
        $this->assertStringContainsString('medication side effects', $allContent);
        $this->assertStringContainsString('Worried about dizziness', $allContent);
    }

    public function test_summarizer_returns_null_for_sessions_with_few_messages(): void
    {
        $session = ChatSession::factory()->create([
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
        ]);

        // Create only 3 messages (below the threshold of 5)
        ChatMessage::factory()->count(3)->create(['session_id' => $session->id]);

        $summarizer = app(SessionSummarizer::class);
        $result = $summarizer->summarize($session);

        $this->assertNull($result);
    }

    public function test_summarizer_creates_summary_for_sessions_with_enough_messages(): void
    {
        $session = ChatSession::factory()->create([
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
        ]);

        // Create 6 messages (above threshold)
        ChatMessage::factory()->count(6)->create(['session_id' => $session->id]);

        // Mock the AnthropicClient to avoid real API calls
        $this->mock(AnthropicClient::class, function (MockInterface $mock) {
            $mock->shouldReceive('chatWithThinking')
                ->once()
                ->andReturn([
                    'text' => json_encode([
                        'summary_text' => 'Patient discussed medication concerns and follow-up schedule.',
                        'key_questions' => ['What are the side effects of metoprolol?'],
                        'concerns_raised' => ['Heart palpitations at night'],
                        'followup_items' => ['Monitor heart rate daily'],
                        'emotional_context' => 'Concerned but cooperative',
                    ]),
                    'thinking' => 'Analyzing the session...',
                    'usage' => ['input_tokens' => 500, 'output_tokens' => 200],
                ]);

            // Allow parseJsonOutput to be called statically (it's a static method)
        });

        $summarizer = app(SessionSummarizer::class);
        $result = $summarizer->summarize($session);

        $this->assertNotNull($result);
        $this->assertInstanceOf(PatientContextSummary::class, $result);
        $this->assertEquals('Patient discussed medication concerns and follow-up schedule.', $result->summary_text);
        $this->assertEquals(['What are the side effects of metoprolol?'], $result->key_questions);
        $this->assertEquals(['Heart palpitations at night'], $result->concerns_raised);
        $this->assertEquals(['Monitor heart rate daily'], $result->followup_items);
        $this->assertEquals('Concerned but cooperative', $result->emotional_context);
        $this->assertGreaterThan(0, $result->token_count);
        $this->assertEquals($this->patient->id, $result->patient_id);
        $this->assertEquals($this->visit->id, $result->visit_id);
        $this->assertEquals($session->id, $result->session_id);
    }

    public function test_migration_runs_without_errors(): void
    {
        // The RefreshDatabase trait already runs all migrations.
        // Verify the table exists and has the expected columns.
        $this->assertTrue(
            \Illuminate\Support\Facades\Schema::hasTable('patient_context_summaries')
        );

        $this->assertTrue(
            \Illuminate\Support\Facades\Schema::hasColumns('patient_context_summaries', [
                'id', 'patient_id', 'visit_id', 'session_id',
                'summary_text', 'key_questions', 'concerns_raised',
                'followup_items', 'emotional_context', 'token_count',
                'created_at', 'updated_at',
            ])
        );
    }

    public function test_feature_flags_endpoint_returns_context_compaction_status(): void
    {
        config(['postvisit.context_compaction_enabled' => false]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/settings/features');

        $response->assertOk()
            ->assertJsonPath('data.context_compaction', false);
    }

    public function test_context_compaction_limits_to_five_summaries(): void
    {
        config(['postvisit.context_compaction_enabled' => true]);

        // Create 7 summaries
        for ($i = 0; $i < 7; $i++) {
            PatientContextSummary::factory()->create([
                'patient_id' => $this->patient->id,
                'visit_id' => $this->visit->id,
                'summary_text' => "Summary number {$i}",
                'created_at' => now()->subDays(7 - $i),
            ]);
        }

        $assembler = app(ContextAssembler::class);
        $result = $assembler->assembleForVisit($this->visit);

        $allContent = collect($result['context_messages'])
            ->pluck('content')
            ->implode("\n");

        // Should contain the 5 most recent (numbers 2-6), not the oldest (0-1)
        $this->assertStringContainsString('Summary number 6', $allContent);
        $this->assertStringContainsString('Summary number 2', $allContent);
        $this->assertStringNotContainsString('Summary number 0', $allContent);
        $this->assertStringNotContainsString('Summary number 1', $allContent);
    }
}
