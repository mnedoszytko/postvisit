<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Patient;
use App\Models\Practitioner;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PolicyTest extends TestCase
{
    use RefreshDatabase;

    private function createVisitWithUsers(): array
    {
        $org = Organization::factory()->create();
        $patient = Patient::factory()->create();
        $practitioner = Practitioner::factory()->create(['organization_id' => $org->id]);

        $patientUser = User::factory()->patient($patient)->create();
        $doctorUser = User::factory()->doctor($practitioner)->create();
        $adminUser = User::factory()->admin()->create();

        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'practitioner_id' => $practitioner->id,
            'organization_id' => $org->id,
        ]);

        return compact('visit', 'patient', 'practitioner', 'patientUser', 'doctorUser', 'adminUser', 'org');
    }

    // --- VisitPolicy ---

    public function test_patient_can_view_own_visit(): void
    {
        $data = $this->createVisitWithUsers();

        $response = $this->actingAs($data['patientUser'])
            ->getJson("/api/v1/visits/{$data['visit']->id}");

        $response->assertOk();
    }

    public function test_patient_cannot_view_other_visit(): void
    {
        $data = $this->createVisitWithUsers();
        $otherPatientUser = User::factory()->patient()->create();

        $response = $this->actingAs($otherPatientUser)
            ->getJson("/api/v1/visits/{$data['visit']->id}");

        $response->assertForbidden();
    }

    public function test_doctor_can_view_own_visit(): void
    {
        $data = $this->createVisitWithUsers();

        $response = $this->actingAs($data['doctorUser'])
            ->getJson("/api/v1/visits/{$data['visit']->id}");

        $response->assertOk();
    }

    public function test_doctor_cannot_view_other_visit(): void
    {
        $data = $this->createVisitWithUsers();
        $otherDoctorUser = User::factory()->doctor()->create();

        $response = $this->actingAs($otherDoctorUser)
            ->getJson("/api/v1/visits/{$data['visit']->id}");

        $response->assertForbidden();
    }

    public function test_admin_can_view_any_visit(): void
    {
        $data = $this->createVisitWithUsers();

        $response = $this->actingAs($data['adminUser'])
            ->getJson("/api/v1/visits/{$data['visit']->id}");

        $response->assertOk();
    }

    // --- PatientPolicy ---

    public function test_patient_can_view_own_record(): void
    {
        $data = $this->createVisitWithUsers();

        $response = $this->actingAs($data['patientUser'])
            ->getJson("/api/v1/patients/{$data['patient']->id}");

        $response->assertOk();
    }

    public function test_patient_cannot_view_other_record(): void
    {
        $data = $this->createVisitWithUsers();
        $otherPatientUser = User::factory()->patient()->create();

        $response = $this->actingAs($otherPatientUser)
            ->getJson("/api/v1/patients/{$data['patient']->id}");

        $response->assertForbidden();
    }

    public function test_doctor_can_view_patient_with_shared_visit(): void
    {
        $data = $this->createVisitWithUsers();

        $response = $this->actingAs($data['doctorUser'])
            ->getJson("/api/v1/patients/{$data['patient']->id}");

        $response->assertOk();
    }

    public function test_doctor_cannot_view_patient_without_shared_visit(): void
    {
        $data = $this->createVisitWithUsers();
        $otherDoctorUser = User::factory()->doctor()->create();

        $response = $this->actingAs($otherDoctorUser)
            ->getJson("/api/v1/patients/{$data['patient']->id}");

        $response->assertForbidden();
    }

    public function test_admin_can_view_any_patient(): void
    {
        $data = $this->createVisitWithUsers();

        $response = $this->actingAs($data['adminUser'])
            ->getJson("/api/v1/patients/{$data['patient']->id}");

        $response->assertOk();
    }

    public function test_patient_can_update_own_record(): void
    {
        $data = $this->createVisitWithUsers();

        $response = $this->actingAs($data['patientUser'])
            ->patchJson("/api/v1/patients/{$data['patient']->id}", [
                'preferred_language' => 'en',
            ]);

        $response->assertOk();
    }

    public function test_patient_cannot_update_other_record(): void
    {
        $data = $this->createVisitWithUsers();
        $otherPatientUser = User::factory()->patient()->create();

        $response = $this->actingAs($otherPatientUser)
            ->patchJson("/api/v1/patients/{$data['patient']->id}", [
                'preferred_language' => 'en',
            ]);

        $response->assertForbidden();
    }
}
