<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_doctor_can_view_audit_logs(): void
    {
        $doctor = User::factory()->doctor()->create();
        AuditLog::factory()->count(5)->create();

        $response = $this->actingAs($doctor)->getJson('/api/v1/audit/logs');

        $response->assertOk()
            ->assertJsonStructure(['data' => ['data']]);
    }

    public function test_audit_logs_can_be_filtered_by_resource_type(): void
    {
        $doctor = User::factory()->doctor()->create();
        AuditLog::factory()->count(3)->create(['resource_type' => 'visit']);
        AuditLog::factory()->count(2)->create(['resource_type' => 'patient']);

        $response = $this->actingAs($doctor)->getJson('/api/v1/audit/logs?resource_type=visit');

        $response->assertOk();
        $items = $response->json('data.data');
        $this->assertCount(3, $items);
    }

    public function test_audit_logs_can_be_filtered_by_action_type(): void
    {
        $doctor = User::factory()->doctor()->create();
        AuditLog::factory()->count(2)->create(['action_type' => 'read']);
        AuditLog::factory()->count(1)->create(['action_type' => 'create']);

        $response = $this->actingAs($doctor)->getJson('/api/v1/audit/logs?action_type=read');

        $response->assertOk();
        $items = $response->json('data.data');
        $this->assertCount(2, $items);
    }

    public function test_patient_cannot_view_audit_logs(): void
    {
        $patient = Patient::factory()->create();
        $user = User::factory()->patient($patient)->create();

        $response = $this->actingAs($user)->getJson('/api/v1/audit/logs');

        $response->assertStatus(403);
    }

    public function test_unauthenticated_cannot_view_audit_logs(): void
    {
        $response = $this->getJson('/api/v1/audit/logs');

        $response->assertStatus(401);
    }
}
