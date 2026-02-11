<?php

namespace Tests\Feature;

use App\Models\Observation;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\Practitioner;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ObservationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Visit $visit;

    private Practitioner $practitioner;

    protected function setUp(): void
    {
        parent::setUp();

        $org = Organization::factory()->create();
        $this->practitioner = Practitioner::factory()->create(['organization_id' => $org->id]);
        $patient = Patient::factory()->create();
        $this->user = User::factory()->patient($patient)->create();
        $this->visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'practitioner_id' => $this->practitioner->id,
            'organization_id' => $org->id,
        ]);
    }

    public function test_can_list_visit_observations(): void
    {
        Observation::factory()->count(3)->create([
            'patient_id' => $this->visit->patient_id,
            'visit_id' => $this->visit->id,
            'practitioner_id' => $this->practitioner->id,
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/visits/{$this->visit->id}/observations");

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_can_filter_observations_by_category(): void
    {
        Observation::factory()->create([
            'patient_id' => $this->visit->patient_id,
            'visit_id' => $this->visit->id,
            'practitioner_id' => $this->practitioner->id,
            'category' => 'laboratory',
            'code_display' => 'Cholesterol',
        ]);

        Observation::factory()->create([
            'patient_id' => $this->visit->patient_id,
            'visit_id' => $this->visit->id,
            'practitioner_id' => $this->practitioner->id,
            'category' => 'vital-signs',
            'code_display' => 'Heart rate',
        ]);

        Observation::factory()->create([
            'patient_id' => $this->visit->patient_id,
            'visit_id' => $this->visit->id,
            'practitioner_id' => $this->practitioner->id,
            'category' => 'exam',
            'code_display' => 'EKG',
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/visits/{$this->visit->id}/observations?category=laboratory");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.code_display', 'Cholesterol');
    }

    public function test_can_view_single_observation(): void
    {
        $observation = Observation::factory()->create([
            'patient_id' => $this->visit->patient_id,
            'visit_id' => $this->visit->id,
            'practitioner_id' => $this->practitioner->id,
            'code_display' => 'Potassium',
            'value_type' => 'quantity',
            'value_quantity' => 4.2,
            'value_unit' => 'mEq/L',
            'interpretation' => 'N',
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/visits/{$this->visit->id}/observations/{$observation->id}");

        $response->assertOk()
            ->assertJsonPath('data.code_display', 'Potassium')
            ->assertJsonPath('data.interpretation', 'N');
    }

    public function test_observation_not_found_returns_404(): void
    {
        $response = $this->actingAs($this->user)->getJson("/api/v1/visits/{$this->visit->id}/observations/nonexistent-id");

        $response->assertStatus(404);
    }

    public function test_observations_require_authentication(): void
    {
        $response = $this->getJson("/api/v1/visits/{$this->visit->id}/observations");

        $response->assertStatus(401);
    }

    public function test_observations_return_empty_when_none_exist(): void
    {
        $response = $this->actingAs($this->user)->getJson("/api/v1/visits/{$this->visit->id}/observations");

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_observations_include_reference_ranges(): void
    {
        Observation::factory()->create([
            'patient_id' => $this->visit->patient_id,
            'visit_id' => $this->visit->id,
            'practitioner_id' => $this->practitioner->id,
            'value_type' => 'quantity',
            'value_quantity' => 215,
            'value_unit' => 'mg/dL',
            'reference_range_low' => 0,
            'reference_range_high' => 200,
            'reference_range_text' => 'Desirable: <200 mg/dL',
            'interpretation' => 'H',
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/visits/{$this->visit->id}/observations");

        $response->assertOk()
            ->assertJsonPath('data.0.interpretation', 'H')
            ->assertJsonPath('data.0.reference_range_text', 'Desirable: <200 mg/dL');
    }
}
