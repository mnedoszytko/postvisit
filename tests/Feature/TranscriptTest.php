<?php

namespace Tests\Feature;

use App\Jobs\ProcessTranscriptJob;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\Practitioner;
use App\Models\Transcript;
use App\Models\User;
use App\Models\Visit;
use App\Services\AI\ScribeProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TranscriptTest extends TestCase
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

    public function test_can_upload_transcript(): void
    {
        $response = $this->actingAs($this->user)->postJson("/api/v1/visits/{$this->visit->id}/transcript", [
            'raw_transcript' => 'Doctor: How are you feeling today? Patient: I have been experiencing headaches.',
            'source_type' => 'manual_upload',
            'patient_consent_given' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.processing_status', 'pending')
            ->assertJsonPath('data.visit_id', $this->visit->id);
    }

    public function test_can_view_transcript(): void
    {
        Transcript::factory()->create([
            'visit_id' => $this->visit->id,
            'patient_id' => $this->visit->patient_id,
            'raw_transcript' => 'Test transcript content',
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/visits/{$this->visit->id}/transcript");

        $response->assertOk()
            ->assertJsonPath('data.raw_transcript', 'Test transcript content');
    }

    public function test_transcript_not_found_returns_null(): void
    {
        $response = $this->actingAs($this->user)->getJson("/api/v1/visits/{$this->visit->id}/transcript");

        $response->assertOk()
            ->assertJsonPath('data', null);
    }

    public function test_can_process_transcript(): void
    {
        Queue::fake();

        Transcript::factory()->create([
            'visit_id' => $this->visit->id,
            'patient_id' => $this->visit->patient_id,
            'processing_status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)->postJson("/api/v1/visits/{$this->visit->id}/transcript/process");

        $response->assertOk()
            ->assertJsonPath('data.processing_status', 'processing');
    }

    public function test_process_returns_404_when_no_transcript(): void
    {
        $response = $this->actingAs($this->user)->postJson("/api/v1/visits/{$this->visit->id}/transcript/process");

        $response->assertStatus(404);
    }

    public function test_can_check_transcript_status(): void
    {
        Transcript::factory()->create([
            'visit_id' => $this->visit->id,
            'patient_id' => $this->visit->patient_id,
            'processing_status' => 'completed',
            'soap_note' => ['subjective' => 'test'],
            'summary' => 'A summary',
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/visits/{$this->visit->id}/transcript/status");

        $response->assertOk()
            ->assertJsonPath('data.processing_status', 'completed')
            ->assertJsonPath('data.has_soap_note', true)
            ->assertJsonPath('data.has_summary', true);
    }

    public function test_transcript_upload_validates_consent(): void
    {
        $response = $this->actingAs($this->user)->postJson("/api/v1/visits/{$this->visit->id}/transcript", [
            'raw_transcript' => 'Some text',
            'source_type' => 'manual_upload',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('patient_consent_given');
    }

    public function test_upload_with_process_flag_dispatches_job(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->user)->postJson("/api/v1/visits/{$this->visit->id}/transcript", [
            'raw_transcript' => 'Doctor: How are you? Patient: Fine, thank you.',
            'source_type' => 'manual_upload',
            'patient_consent_given' => true,
            'process' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.processing_status', 'processing');

        Queue::assertPushed(ProcessTranscriptJob::class);
    }

    public function test_upload_with_demo_transcript_loads_file(): void
    {
        $response = $this->actingAs($this->user)->postJson("/api/v1/visits/{$this->visit->id}/transcript", [
            'use_demo_transcript' => true,
            'source_type' => 'manual_upload',
            'patient_consent_given' => true,
        ]);

        $response->assertStatus(201);

        $transcript = Transcript::where('visit_id', $this->visit->id)->first();

        $this->assertNotNull($transcript);
        $this->assertStringContainsString('DR. NEDO: Good morning', $transcript->raw_transcript);
        $this->assertEquals('whisper', $transcript->stt_provider);
        $this->assertEquals(1590, $transcript->audio_duration_seconds);
    }

    public function test_process_already_completed_returns_early(): void
    {
        Transcript::factory()->create([
            'visit_id' => $this->visit->id,
            'patient_id' => $this->visit->patient_id,
            'processing_status' => 'completed',
            'soap_note' => ['subjective' => 'test'],
        ]);

        $response = $this->actingAs($this->user)->postJson("/api/v1/visits/{$this->visit->id}/transcript/process");

        $response->assertOk()
            ->assertJsonPath('message', 'Transcript already processed');
    }

    public function test_process_async_dispatches_job(): void
    {
        Queue::fake();

        Transcript::factory()->create([
            'visit_id' => $this->visit->id,
            'patient_id' => $this->visit->patient_id,
            'processing_status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)->postJson("/api/v1/visits/{$this->visit->id}/transcript/process");

        $response->assertOk()
            ->assertJsonPath('data.processing_status', 'processing');

        Queue::assertPushed(ProcessTranscriptJob::class);
    }

    public function test_process_sync_requires_scribe_processor(): void
    {
        $fakeResult = [
            'extracted_entities' => ['medications' => ['propranolol']],
            'soap_note' => [
                'subjective' => 'test',
                'objective' => 'test',
                'assessment' => 'test',
                'plan' => 'test',
            ],
            'clean_transcript' => 'test',
            'speakers' => [],
        ];

        $this->mock(ScribeProcessor::class)
            ->shouldReceive('process')
            ->once()
            ->andReturn($fakeResult);

        $transcript = Transcript::factory()->create([
            'visit_id' => $this->visit->id,
            'patient_id' => $this->visit->patient_id,
            'processing_status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)->postJson("/api/v1/visits/{$this->visit->id}/transcript/process", [
            'sync' => true,
        ]);

        $response->assertOk();

        $transcript->refresh();
        $this->assertEquals('completed', $transcript->processing_status);

        $this->assertDatabaseHas('visit_notes', [
            'visit_id' => $this->visit->id,
            'assessment' => 'test',
            'plan' => 'test',
        ]);
    }

    public function test_status_returns_404_when_no_transcript(): void
    {
        $response = $this->actingAs($this->user)->getJson("/api/v1/visits/{$this->visit->id}/transcript/status");

        $response->assertStatus(404);
    }
}
