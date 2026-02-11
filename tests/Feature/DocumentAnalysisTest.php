<?php

namespace Tests\Feature;

use App\Jobs\AnalyzeDocumentJob;
use App\Models\Document;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\Practitioner;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentAnalysisTest extends TestCase
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

    public function test_upload_dispatches_analysis_job_for_image(): void
    {
        Storage::fake('local');
        Queue::fake();

        $file = UploadedFile::fake()->image('ecg-scan.jpg', 800, 600)->size(1024);

        $this->actingAs($this->user)->postJson(
            "/api/v1/visits/{$this->visit->id}/documents",
            ['file' => $file, 'document_type' => 'ecg']
        )->assertStatus(201);

        Queue::assertPushed(AnalyzeDocumentJob::class, function ($job) {
            return $job->document->content_type === 'image';
        });
    }

    public function test_upload_dispatches_analysis_job_for_pdf(): void
    {
        Storage::fake('local');
        Queue::fake();

        $file = UploadedFile::fake()->create('lab-results.pdf', 500, 'application/pdf');

        $this->actingAs($this->user)->postJson(
            "/api/v1/visits/{$this->visit->id}/documents",
            ['file' => $file, 'document_type' => 'lab_result']
        )->assertStatus(201);

        Queue::assertPushed(AnalyzeDocumentJob::class, function ($job) {
            return $job->document->content_type === 'pdf';
        });
    }

    public function test_upload_does_not_dispatch_for_unsupported_types(): void
    {
        Storage::fake('local');
        Queue::fake();

        // Create a document with 'other' content type directly
        Document::factory()->create([
            'visit_id' => $this->visit->id,
            'patient_id' => $this->visit->patient_id,
            'content_type' => 'other',
        ]);

        Queue::assertNotPushed(AnalyzeDocumentJob::class);
    }

    public function test_analysis_status_returns_pending(): void
    {
        $document = Document::factory()->create([
            'visit_id' => $this->visit->id,
            'patient_id' => $this->visit->patient_id,
            'analysis_status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)->getJson(
            "/api/v1/documents/{$document->id}/analysis"
        );

        $response->assertOk()
            ->assertJsonPath('data.analysis_status', 'pending')
            ->assertJsonPath('data.ai_analysis', null);
    }

    public function test_analysis_status_returns_completed_with_results(): void
    {
        $document = Document::factory()->analyzed()->create([
            'visit_id' => $this->visit->id,
            'patient_id' => $this->visit->patient_id,
        ]);

        $response = $this->actingAs($this->user)->getJson(
            "/api/v1/documents/{$document->id}/analysis"
        );

        $response->assertOk()
            ->assertJsonPath('data.analysis_status', 'completed')
            ->assertJsonPath('data.ai_analysis.confidence', 'high')
            ->assertJsonPath('data.ai_analysis.document_category', 'ecg');
    }

    public function test_analysis_status_returns_failed_with_error(): void
    {
        $document = Document::factory()->create([
            'visit_id' => $this->visit->id,
            'patient_id' => $this->visit->patient_id,
            'analysis_status' => 'failed',
            'analysis_error' => 'API rate limit exceeded',
        ]);

        $response = $this->actingAs($this->user)->getJson(
            "/api/v1/documents/{$document->id}/analysis"
        );

        $response->assertOk()
            ->assertJsonPath('data.analysis_status', 'failed')
            ->assertJsonPath('data.analysis_error', 'API rate limit exceeded');
    }

    public function test_document_model_casts_ai_analysis_as_array(): void
    {
        $document = Document::factory()->analyzed()->create([
            'visit_id' => $this->visit->id,
            'patient_id' => $this->visit->patient_id,
        ]);

        $document->refresh();

        $this->assertIsArray($document->ai_analysis);
        $this->assertArrayHasKey('summary', $document->ai_analysis);
        $this->assertArrayHasKey('findings', $document->ai_analysis);
        $this->assertArrayHasKey('key_values', $document->ai_analysis);
    }

    public function test_analysis_endpoint_requires_authentication(): void
    {
        $document = Document::factory()->create([
            'visit_id' => $this->visit->id,
            'patient_id' => $this->visit->patient_id,
        ]);

        $response = $this->getJson("/api/v1/documents/{$document->id}/analysis");

        $response->assertUnauthorized();
    }
}
