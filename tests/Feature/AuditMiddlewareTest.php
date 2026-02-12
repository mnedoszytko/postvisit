<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\Practitioner;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Patient $patient;

    private Practitioner $practitioner;

    private Organization $organization;

    private Visit $visit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->create();
        $this->practitioner = Practitioner::factory()->create(['organization_id' => $this->organization->id]);
        $this->patient = Patient::factory()->create();
        $this->user = User::factory()->patient($this->patient)->create();
        $this->visit = Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'practitioner_id' => $this->practitioner->id,
            'organization_id' => $this->organization->id,
        ]);
    }

    public function test_phi_route_access_creates_audit_log(): void
    {
        $this->actingAs($this->user)
            ->getJson("/api/v1/patients/{$this->patient->id}");

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->user->id,
        ]);
    }

    public function test_audit_log_captures_correct_user_info(): void
    {
        $this->actingAs($this->user)
            ->getJson("/api/v1/patients/{$this->patient->id}");

        $log = AuditLog::where('user_id', $this->user->id)->first();

        $this->assertNotNull($log);
        $this->assertEquals($this->user->id, $log->user_id);
        $this->assertEquals('patient', $log->user_role);
    }

    public function test_get_request_maps_to_read_action(): void
    {
        $this->actingAs($this->user)
            ->getJson("/api/v1/patients/{$this->patient->id}");

        $log = AuditLog::where('user_id', $this->user->id)->first();

        $this->assertNotNull($log);
        $this->assertEquals('read', $log->action_type);
    }

    public function test_post_request_maps_to_create_action(): void
    {
        $this->actingAs($this->user)
            ->postJson("/api/v1/patients/{$this->patient->id}/conditions", [
                'condition_name' => 'Hypertension',
                'clinical_status' => 'active',
                'verification_status' => 'confirmed',
            ]);

        $log = AuditLog::where('user_id', $this->user->id)->first();

        $this->assertNotNull($log);
        $this->assertEquals('create', $log->action_type);
    }

    public function test_audit_log_captures_resource_type_and_id(): void
    {
        $this->actingAs($this->user)
            ->getJson("/api/v1/patients/{$this->patient->id}");

        $log = AuditLog::where('user_id', $this->user->id)->first();

        $this->assertNotNull($log);
        $this->assertEquals('patient', $log->resource_type);
        $this->assertEquals($this->patient->id, $log->resource_id);
    }

    public function test_audit_log_marks_phi_accessed(): void
    {
        $this->actingAs($this->user)
            ->getJson("/api/v1/patients/{$this->patient->id}");

        $log = AuditLog::where('user_id', $this->user->id)->first();

        $this->assertNotNull($log);
        $this->assertTrue($log->phi_accessed);
    }

    public function test_audit_log_captures_ip_address(): void
    {
        $this->actingAs($this->user)
            ->getJson("/api/v1/patients/{$this->patient->id}");

        $log = AuditLog::where('user_id', $this->user->id)->first();

        $this->assertNotNull($log);
        $this->assertNotEmpty($log->ip_address);
    }

    public function test_failed_request_logged_with_success_false(): void
    {
        $nonExistentId = '00000000-0000-0000-0000-000000000000';

        $this->actingAs($this->user)
            ->getJson("/api/v1/patients/{$nonExistentId}");

        $log = AuditLog::where('user_id', $this->user->id)->first();

        $this->assertNotNull($log);
        $this->assertFalse($log->success);
    }

    public function test_unauthenticated_request_not_logged(): void
    {
        $this->getJson("/api/v1/patients/{$this->patient->id}");

        $this->assertDatabaseCount('audit_logs', 0);
    }
}
