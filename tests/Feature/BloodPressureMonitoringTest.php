<?php

namespace Tests\Feature;

use App\Models\Observation;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\Practitioner;
use App\Models\User;
use App\Models\Visit;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BloodPressureMonitoringTest extends TestCase
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

    public function test_doctor_can_fetch_bp_observations(): void
    {
        $visit = Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'practitioner_id' => $this->practitioner->id,
            'organization_id' => $this->organization->id,
        ]);

        Observation::factory()->create([
            'patient_id' => $this->patient->id,
            'visit_id' => $visit->id,
            'code' => '85354-9',
            'code_display' => 'Blood pressure panel',
            'value_type' => 'string',
            'value_string' => '140/90 mmHg',
            'specialty_data' => [
                'systolic' => ['value' => 140, 'unit' => 'mmHg'],
                'diastolic' => ['value' => 90, 'unit' => 'mmHg'],
            ],
        ]);

        $response = $this->actingAs($this->doctorUser)
            ->getJson("/api/v1/doctor/patients/{$this->patient->id}/observations?code=85354-9");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.code', '85354-9')
            ->assertJsonPath('data.0.specialty_data.systolic.value', 140);
    }

    public function test_bp_readings_include_htn_staging(): void
    {
        $visit = Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'practitioner_id' => $this->practitioner->id,
            'organization_id' => $this->organization->id,
        ]);

        Observation::factory()->create([
            'patient_id' => $this->patient->id,
            'visit_id' => $visit->id,
            'code' => '85354-9',
            'value_type' => 'string',
            'value_string' => '148/92 mmHg',
            'specialty_data' => [
                'systolic' => ['value' => 148, 'unit' => 'mmHg'],
                'diastolic' => ['value' => 92, 'unit' => 'mmHg'],
                'htn_stage' => 'stage_2',
            ],
        ]);

        $response = $this->actingAs($this->doctorUser)
            ->getJson("/api/v1/doctor/patients/{$this->patient->id}/observations?code=85354-9");

        $response->assertOk()
            ->assertJsonPath('data.0.specialty_data.htn_stage', 'stage_2');
    }

    public function test_seeder_creates_bp_series(): void
    {
        $this->seed(DemoSeeder::class);

        $patient = Patient::where('email', 'patient@demo.postvisit.ai')->first();

        $bpReadings = Observation::where('patient_id', $patient->id)
            ->where('code', '85354-9')
            ->get();

        // 1 original + 9 series + 9 home readings = 19 total
        $this->assertEquals(19, $bpReadings->count());

        // Check all have specialty_data with systolic/diastolic
        foreach ($bpReadings as $bp) {
            $this->assertArrayHasKey('systolic', $bp->specialty_data);
            $this->assertArrayHasKey('diastolic', $bp->specialty_data);
        }
    }

    public function test_seeder_bp_series_has_correct_interpretations(): void
    {
        $this->seed(DemoSeeder::class);

        $patient = Patient::where('email', 'patient@demo.postvisit.ai')->first();

        $bpReadings = Observation::where('patient_id', $patient->id)
            ->where('code', '85354-9')
            ->get();

        $highCount = $bpReadings->where('interpretation', 'H')->count();
        $normalCount = $bpReadings->where('interpretation', 'N')->count();

        $this->assertGreaterThan(0, $highCount, 'Should have elevated BP readings');
        $this->assertGreaterThan(0, $normalCount, 'Should have normal BP readings');
    }

    public function test_patient_cannot_access_bp_observations(): void
    {
        $patientUser = User::factory()->patient($this->patient)->create();

        $response = $this->actingAs($patientUser)
            ->getJson("/api/v1/doctor/patients/{$this->patient->id}/observations?code=85354-9");

        $response->assertStatus(403);
    }
}
