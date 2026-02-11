<?php

namespace Tests\Feature;

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

class DocumentUploadTest extends TestCase
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

    public function test_can_upload_image_to_visit(): void
    {
        Storage::fake('local');
        Queue::fake();

        $file = UploadedFile::fake()->image('ecg-scan.jpg', 800, 600)->size(1024);

        $response = $this->actingAs($this->user)->postJson(
            "/api/v1/visits/{$this->visit->id}/documents",
            [
                'file' => $file,
                'title' => 'My ECG Scan',
                'document_type' => 'ecg',
            ]
        );

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'My ECG Scan')
            ->assertJsonPath('data.document_type', 'ecg')
            ->assertJsonPath('data.content_type', 'image')
            ->assertJsonPath('data.visit_id', $this->visit->id);

        $this->assertDatabaseHas('documents', [
            'visit_id' => $this->visit->id,
            'title' => 'My ECG Scan',
        ]);
    }

    public function test_can_upload_pdf_to_visit(): void
    {
        Storage::fake('local');
        Queue::fake();

        $file = UploadedFile::fake()->create('lab-results.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->user)->postJson(
            "/api/v1/visits/{$this->visit->id}/documents",
            [
                'file' => $file,
                'document_type' => 'lab_result',
            ]
        );

        $response->assertStatus(201)
            ->assertJsonPath('data.content_type', 'pdf')
            ->assertJsonPath('data.document_type', 'lab_result');
    }

    public function test_upload_uses_original_filename_as_default_title(): void
    {
        Storage::fake('local');
        Queue::fake();

        $file = UploadedFile::fake()->image('heart-echo.png', 400, 300);

        $response = $this->actingAs($this->user)->postJson(
            "/api/v1/visits/{$this->visit->id}/documents",
            ['file' => $file]
        );

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'heart-echo.png');
    }

    public function test_upload_rejects_invalid_file_type(): void
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->create('script.exe', 100, 'application/x-msdownload');

        $response = $this->actingAs($this->user)->postJson(
            "/api/v1/visits/{$this->visit->id}/documents",
            ['file' => $file]
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors('file');
    }

    public function test_upload_rejects_oversized_file(): void
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->image('huge.jpg')->size(25000); // 25 MB

        $response = $this->actingAs($this->user)->postJson(
            "/api/v1/visits/{$this->visit->id}/documents",
            ['file' => $file]
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors('file');
    }

    public function test_can_list_visit_documents(): void
    {
        Document::factory()->count(3)->create([
            'visit_id' => $this->visit->id,
            'patient_id' => $this->visit->patient_id,
        ]);

        $response = $this->actingAs($this->user)->getJson(
            "/api/v1/visits/{$this->visit->id}/documents"
        );

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_can_delete_document(): void
    {
        Storage::fake('local');

        $document = Document::factory()->create([
            'visit_id' => $this->visit->id,
            'patient_id' => $this->visit->patient_id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->deleteJson(
            "/api/v1/documents/{$document->id}"
        );

        $response->assertOk();
        $this->assertDatabaseMissing('documents', ['id' => $document->id]);
    }

    public function test_upload_requires_authentication(): void
    {
        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->postJson(
            "/api/v1/visits/{$this->visit->id}/documents",
            ['file' => $file]
        );

        $response->assertUnauthorized();
    }
}
