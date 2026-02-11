<?php

namespace Tests\Feature;

use App\Models\Observation;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\Practitioner;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DoctorAlertsTest extends TestCase
{
    use RefreshDatabase;

    private User $doctorUser;

    private Practitioner $practitioner;

    private Organization $organization;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->create();
        $this->practitioner = Practitioner::factory()->create(['organization_id' => $this->organization->id]);
        $this->doctorUser = User::factory()->doctor($this->practitioner)->create();
        $this->patient = Patient::factory()->create();
    }

    public function test_alerts_returns_empty_when_no_observations(): void
    {
        Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'practitioner_id' => $this->practitioner->id,
            'organization_id' => $this->organization->id,
        ]);

        $response = $this->actingAs($this->doctorUser)->getJson('/api/v1/doctor/alerts');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_alerts_detects_weight_gain(): void
    {
        $visit = Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'practitioner_id' => $this->practitioner->id,
            'organization_id' => $this->organization->id,
        ]);

        // Create weight observations showing >= 2kg gain in 3 days
        Observation::factory()->create([
            'patient_id' => $this->patient->id,
            'visit_id' => $visit->id,
            'code' => '29463-7',
            'code_display' => 'Body weight',
            'value_type' => 'quantity',
            'value_quantity' => 80.0,
            'value_unit' => 'kg',
            'effective_date' => Carbon::now()->subDays(2)->toDateString(),
        ]);

        Observation::factory()->create([
            'patient_id' => $this->patient->id,
            'visit_id' => $visit->id,
            'code' => '29463-7',
            'code_display' => 'Body weight',
            'value_type' => 'quantity',
            'value_quantity' => 82.5,
            'value_unit' => 'kg',
            'effective_date' => Carbon::now()->toDateString(),
        ]);

        $response = $this->actingAs($this->doctorUser)->getJson('/api/v1/doctor/alerts');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'weight_gain')
            ->assertJsonPath('data.0.severity', 'high')
            ->assertJsonPath('data.0.patient_id', $this->patient->id)
            ->assertJsonPath('data.0.data.delta_kg', 2.5);
    }

    public function test_alerts_ignores_weight_gain_below_threshold(): void
    {
        $visit = Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'practitioner_id' => $this->practitioner->id,
            'organization_id' => $this->organization->id,
        ]);

        Observation::factory()->create([
            'patient_id' => $this->patient->id,
            'visit_id' => $visit->id,
            'code' => '29463-7',
            'value_quantity' => 80.0,
            'value_unit' => 'kg',
            'effective_date' => Carbon::now()->subDays(2)->toDateString(),
        ]);

        Observation::factory()->create([
            'patient_id' => $this->patient->id,
            'visit_id' => $visit->id,
            'code' => '29463-7',
            'value_quantity' => 81.5,
            'value_unit' => 'kg',
            'effective_date' => Carbon::now()->toDateString(),
        ]);

        $response = $this->actingAs($this->doctorUser)->getJson('/api/v1/doctor/alerts');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_alerts_detects_elevated_bp_trend(): void
    {
        $visit = Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'practitioner_id' => $this->practitioner->id,
            'organization_id' => $this->organization->id,
        ]);

        // Create 3 consecutive elevated BP readings
        foreach ([1, 2, 3] as $daysAgo) {
            Observation::factory()->create([
                'patient_id' => $this->patient->id,
                'visit_id' => $visit->id,
                'code' => '85354-9',
                'code_display' => 'Blood pressure panel',
                'value_type' => 'quantity',
                'value_quantity' => 145,
                'value_unit' => 'mmHg',
                'specialty_data' => [
                    'systolic' => 145,
                    'diastolic' => 92,
                ],
                'effective_date' => Carbon::now()->subDays($daysAgo)->toDateString(),
            ]);
        }

        $response = $this->actingAs($this->doctorUser)->getJson('/api/v1/doctor/alerts');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'elevated_bp')
            ->assertJsonPath('data.0.severity', 'medium')
            ->assertJsonPath('data.0.data.consecutive_count', 3);
    }

    public function test_alerts_ignores_non_consecutive_elevated_bp(): void
    {
        $visit = Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'practitioner_id' => $this->practitioner->id,
            'organization_id' => $this->organization->id,
        ]);

        // 2 elevated, 1 normal, 1 elevated — no 3 consecutive
        Observation::factory()->create([
            'patient_id' => $this->patient->id,
            'visit_id' => $visit->id,
            'code' => '85354-9',
            'specialty_data' => ['systolic' => 145, 'diastolic' => 92],
            'effective_date' => Carbon::now()->subDays(4)->toDateString(),
        ]);
        Observation::factory()->create([
            'patient_id' => $this->patient->id,
            'visit_id' => $visit->id,
            'code' => '85354-9',
            'specialty_data' => ['systolic' => 150, 'diastolic' => 95],
            'effective_date' => Carbon::now()->subDays(3)->toDateString(),
        ]);
        Observation::factory()->create([
            'patient_id' => $this->patient->id,
            'visit_id' => $visit->id,
            'code' => '85354-9',
            'specialty_data' => ['systolic' => 120, 'diastolic' => 78],
            'effective_date' => Carbon::now()->subDays(2)->toDateString(),
        ]);
        Observation::factory()->create([
            'patient_id' => $this->patient->id,
            'visit_id' => $visit->id,
            'code' => '85354-9',
            'specialty_data' => ['systolic' => 142, 'diastolic' => 91],
            'effective_date' => Carbon::now()->subDays(1)->toDateString(),
        ]);

        $response = $this->actingAs($this->doctorUser)->getJson('/api/v1/doctor/alerts');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_alerts_returns_both_weight_and_bp_alerts(): void
    {
        $visit = Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'practitioner_id' => $this->practitioner->id,
            'organization_id' => $this->organization->id,
        ]);

        // Weight alert
        Observation::factory()->create([
            'patient_id' => $this->patient->id,
            'visit_id' => $visit->id,
            'code' => '29463-7',
            'value_quantity' => 80.0,
            'value_unit' => 'kg',
            'effective_date' => Carbon::now()->subDays(2)->toDateString(),
        ]);
        Observation::factory()->create([
            'patient_id' => $this->patient->id,
            'visit_id' => $visit->id,
            'code' => '29463-7',
            'value_quantity' => 83.0,
            'value_unit' => 'kg',
            'effective_date' => Carbon::now()->toDateString(),
        ]);

        // BP alert
        foreach ([1, 2, 3] as $daysAgo) {
            Observation::factory()->create([
                'patient_id' => $this->patient->id,
                'visit_id' => $visit->id,
                'code' => '85354-9',
                'specialty_data' => ['systolic' => 150, 'diastolic' => 95],
                'effective_date' => Carbon::now()->subDays($daysAgo)->toDateString(),
            ]);
        }

        $response = $this->actingAs($this->doctorUser)->getJson('/api/v1/doctor/alerts');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.type', 'weight_gain')
            ->assertJsonPath('data.1.type', 'elevated_bp');
    }

    public function test_patient_cannot_access_alerts(): void
    {
        $patientUser = User::factory()->patient($this->patient)->create();

        $response = $this->actingAs($patientUser)->getJson('/api/v1/doctor/alerts');

        $response->assertStatus(403);
    }

    public function test_unauthenticated_cannot_access_alerts(): void
    {
        $response = $this->getJson('/api/v1/doctor/alerts');

        $response->assertStatus(401);
    }

    public function test_patients_include_status_and_condition(): void
    {
        $visit = Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'practitioner_id' => $this->practitioner->id,
            'organization_id' => $this->organization->id,
        ]);

        $response = $this->actingAs($this->doctorUser)->getJson('/api/v1/doctor/patients');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure([
                'data' => [
                    ['id', 'first_name', 'last_name', 'visits_count', 'status', 'last_visit_date'],
                ],
            ]);
    }
}
