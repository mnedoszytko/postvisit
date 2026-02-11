<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Patient;
use App\Models\Practitioner;
use App\Models\UploadToken;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadTokenTest extends TestCase
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

    public function test_can_create_upload_token_for_visit(): void
    {
        $response = $this->actingAs($this->user)->postJson(
            "/api/v1/visits/{$this->visit->id}/upload-tokens"
        );

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['token', 'url', 'expires_at'],
            ]);

        $this->assertDatabaseHas('upload_tokens', [
            'visit_id' => $this->visit->id,
            'created_by' => $this->user->id,
        ]);
    }

    public function test_token_status_returns_pending_when_fresh(): void
    {
        $token = UploadToken::create([
            'token' => 'test-token-pending',
            'visit_id' => $this->visit->id,
            'created_by' => $this->user->id,
            'expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->actingAs($this->user)->getJson(
            '/api/v1/upload-tokens/test-token-pending/status'
        );

        $response->assertOk()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.document', null);
    }

    public function test_token_status_returns_expired_after_expiry(): void
    {
        $token = UploadToken::create([
            'token' => 'test-token-expired',
            'visit_id' => $this->visit->id,
            'created_by' => $this->user->id,
            'expires_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($this->user)->getJson(
            '/api/v1/upload-tokens/test-token-expired/status'
        );

        $response->assertOk()
            ->assertJsonPath('data.status', 'expired');
    }

    public function test_mobile_upload_page_renders_for_valid_token(): void
    {
        $token = UploadToken::create([
            'token' => 'test-token-render',
            'visit_id' => $this->visit->id,
            'created_by' => $this->user->id,
            'expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->get('/upload/test-token-render');

        $response->assertOk()
            ->assertViewIs('upload')
            ->assertSee('PostVisit');
    }

    public function test_mobile_upload_page_returns_410_for_expired_token(): void
    {
        $token = UploadToken::create([
            'token' => 'test-token-expired-page',
            'visit_id' => $this->visit->id,
            'created_by' => $this->user->id,
            'expires_at' => now()->subMinute(),
        ]);

        $response = $this->get('/upload/test-token-expired-page');

        $response->assertStatus(410);
    }

    public function test_mobile_upload_page_returns_410_for_used_token(): void
    {
        $token = UploadToken::create([
            'token' => 'test-token-used-page',
            'visit_id' => $this->visit->id,
            'created_by' => $this->user->id,
            'expires_at' => now()->addMinutes(15),
            'used_at' => now(),
        ]);

        $response = $this->get('/upload/test-token-used-page');

        $response->assertStatus(410);
    }

    public function test_mobile_upload_stores_document_and_marks_token_used(): void
    {
        Storage::fake('local');
        Queue::fake();

        $token = UploadToken::create([
            'token' => 'test-token-upload',
            'visit_id' => $this->visit->id,
            'created_by' => $this->user->id,
            'expires_at' => now()->addMinutes(15),
        ]);

        $file = UploadedFile::fake()->image('medication-box.jpg', 800, 600)->size(1024);

        $response = $this->postJson('/upload/test-token-upload', [
            'file' => $file,
            'document_type' => 'photo',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.document_type', 'photo')
            ->assertJsonPath('data.content_type', 'image')
            ->assertJsonPath('data.visit_id', $this->visit->id);

        $this->assertDatabaseHas('upload_tokens', [
            'token' => 'test-token-upload',
            'used_at' => now()->format('Y-m-d H:i:s'),
        ]);

        $this->assertDatabaseHas('documents', [
            'visit_id' => $this->visit->id,
            'document_type' => 'photo',
        ]);
    }

    public function test_second_upload_with_same_token_is_rejected(): void
    {
        Storage::fake('local');
        Queue::fake();

        $token = UploadToken::create([
            'token' => 'test-token-double',
            'visit_id' => $this->visit->id,
            'created_by' => $this->user->id,
            'expires_at' => now()->addMinutes(15),
            'used_at' => now(),
        ]);

        $file = UploadedFile::fake()->image('second-attempt.jpg', 400, 300);

        $response = $this->postJson('/upload/test-token-double', [
            'file' => $file,
        ]);

        $response->assertStatus(410);
    }

    public function test_token_creation_requires_authentication(): void
    {
        $response = $this->postJson(
            "/api/v1/visits/{$this->visit->id}/upload-tokens"
        );

        $response->assertUnauthorized();
    }

    public function test_token_status_check_requires_authentication(): void
    {
        $token = UploadToken::create([
            'token' => 'test-token-auth',
            'visit_id' => $this->visit->id,
            'created_by' => $this->user->id,
            'expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->getJson('/api/v1/upload-tokens/test-token-auth/status');

        $response->assertUnauthorized();
    }

    public function test_token_status_returns_completed_with_document(): void
    {
        Storage::fake('local');
        Queue::fake();

        // First upload a document through the token flow
        $token = UploadToken::create([
            'token' => 'test-token-completed',
            'visit_id' => $this->visit->id,
            'created_by' => $this->user->id,
            'expires_at' => now()->addMinutes(15),
        ]);

        $file = UploadedFile::fake()->image('lab-result.jpg', 800, 600)->size(512);

        $this->postJson('/upload/test-token-completed', [
            'file' => $file,
            'document_type' => 'lab_result',
        ]);

        // Now check status
        $response = $this->actingAs($this->user)->getJson(
            '/api/v1/upload-tokens/test-token-completed/status'
        );

        $response->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonStructure([
                'data' => ['status', 'document' => ['id', 'title', 'document_type']],
            ]);
    }
}
