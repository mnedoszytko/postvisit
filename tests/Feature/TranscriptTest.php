<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Patient;
use App\Models\Practitioner;
use App\Models\Transcript;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
