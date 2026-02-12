<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoScenarioTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_demo_scenarios(): void
    {
        $response = $this->getJson('/api/v1/demo/scenarios');

        $response->assertOk()
            ->assertJsonCount(12, 'data')
            ->assertJsonPath('data.0.key', 'pvcs')
            ->assertJsonPath('data.0.name', 'PVCs / Palpitations')
            ->assertJsonPath('data.0.patient_name', 'Alex Johnson')
            ->assertJsonPath('data.1.key', 'coronarography')
            ->assertJsonPath('data.1.name', 'Coronarography / Stenosis');
    }

    public function test_can_start_pvcs_scenario(): void
    {
        $response = $this->postJson('/api/v1/demo/start-scenario', [
            'scenario' => 'pvcs',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['user', 'token', 'visit', 'scenario'],
            ])
            ->assertJsonPath('data.user.role', 'patient')
            ->assertJsonPath('data.scenario', 'pvcs');

        $this->assertDatabaseHas('patients', [
            'first_name' => 'Alex',
            'last_name' => 'Johnson',
        ]);

        $this->assertDatabaseHas('conditions', [
            'code' => 'I49.3',
            'code_display' => 'Premature ventricular contractions',
        ]);
    }

    public function test_can_start_coronarography_scenario(): void
    {
        $response = $this->postJson('/api/v1/demo/start-scenario', [
            'scenario' => 'coronarography',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.user.role', 'patient')
            ->assertJsonPath('data.scenario', 'coronarography');

        $this->assertDatabaseHas('patients', [
            'first_name' => 'Marie',
            'last_name' => 'Dupont',
        ]);

        // Verify conditions were loaded from JSON
        $this->assertDatabaseHas('conditions', [
            'code' => 'I25.1',
            'code_display' => 'Atherosclerotic heart disease',
        ]);

        // Verify medications were loaded from JSON
        $this->assertDatabaseHas('medications', [
            'generic_name' => 'Rosuvastatin',
        ]);

        // Verify lab observations were loaded from JSON
        $this->assertDatabaseHas('observations', [
            'code' => '2093-3',
            'code_display' => 'Total Cholesterol',
        ]);
    }

    public function test_start_scenario_validates_input(): void
    {
        $response = $this->postJson('/api/v1/demo/start-scenario', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('scenario');
    }

    public function test_start_scenario_rejects_unknown_scenario(): void
    {
        $response = $this->postJson('/api/v1/demo/start-scenario', [
            'scenario' => 'nonexistent',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.message', 'Unknown scenario: nonexistent');
    }

    public function test_doctor_is_shared_across_scenarios(): void
    {
        $this->postJson('/api/v1/demo/start-scenario', ['scenario' => 'pvcs']);
        $this->postJson('/api/v1/demo/start-scenario', ['scenario' => 'coronarography']);

        $doctorCount = User::where('email', 'doctor@demo.postvisit.ai')->count();
        $this->assertEquals(1, $doctorCount);
    }

    public function test_each_scenario_creates_fresh_user(): void
    {
        $r1 = $this->postJson('/api/v1/demo/start-scenario', ['scenario' => 'pvcs']);
        $r2 = $this->postJson('/api/v1/demo/start-scenario', ['scenario' => 'pvcs']);

        $this->assertNotEquals(
            $r1->json('data.user.id'),
            $r2->json('data.user.id'),
        );
    }

    public function test_scenario_creates_visit_note_with_medical_terms(): void
    {
        $this->postJson('/api/v1/demo/start-scenario', ['scenario' => 'pvcs']);

        $this->assertDatabaseHas('visit_notes', [
            'chief_complaint' => 'Heart palpitations and irregular heartbeat for 3 weeks',
        ]);
    }

    public function test_can_start_diabetes_scenario(): void
    {
        $response = $this->postJson('/api/v1/demo/start-scenario', [
            'scenario' => 'diabetes-management',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.user.role', 'patient')
            ->assertJsonPath('data.scenario', 'diabetes-management');

        $this->assertDatabaseHas('patients', [
            'first_name' => 'Carlos',
            'last_name' => 'Rodriguez',
        ]);

        $this->assertDatabaseHas('conditions', [
            'code' => 'E11.65',
        ]);

        $this->assertDatabaseHas('medications', [
            'generic_name' => 'Metformin',
        ]);

        $this->assertDatabaseHas('observations', [
            'code' => '4548-4',
            'code_display' => 'HbA1c',
        ]);

        // Verify specialty practitioner was created
        $this->assertDatabaseHas('users', [
            'email' => 'dr.patel@demo.postvisit.ai',
            'role' => 'doctor',
        ]);
    }

    public function test_can_start_crohns_scenario(): void
    {
        $response = $this->postJson('/api/v1/demo/start-scenario', [
            'scenario' => 'crohns-flare',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.user.role', 'patient')
            ->assertJsonPath('data.scenario', 'crohns-flare');

        $this->assertDatabaseHas('patients', [
            'first_name' => 'Yukita',
            'last_name' => 'Naka',
        ]);

        $this->assertDatabaseHas('conditions', [
            'code' => 'K50.90',
        ]);

        $this->assertDatabaseHas('medications', [
            'generic_name' => 'Adalimumab',
        ]);

        $this->assertDatabaseHas('observations', [
            'code' => '1988-5',
            'code_display' => 'C-Reactive Protein',
        ]);
    }

    public function test_can_start_copd_scenario(): void
    {
        $response = $this->postJson('/api/v1/demo/start-scenario', [
            'scenario' => 'copd-exacerbation',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.user.role', 'patient')
            ->assertJsonPath('data.scenario', 'copd-exacerbation');

        $this->assertDatabaseHas('patients', [
            'first_name' => 'James',
            'last_name' => 'Washington',
        ]);

        $this->assertDatabaseHas('conditions', [
            'code' => 'J44.1',
        ]);

        $this->assertDatabaseHas('medications', [
            'generic_name' => 'Tiotropium bromide',
        ]);

        $this->assertDatabaseHas('observations', [
            'code' => '6690-2',
            'code_display' => 'WBC (White Blood Cell Count)',
        ]);
    }

    public function test_specialty_practitioners_are_separate_from_default_doctor(): void
    {
        $this->postJson('/api/v1/demo/start-scenario', ['scenario' => 'pvcs']);
        $this->postJson('/api/v1/demo/start-scenario', ['scenario' => 'diabetes-management']);

        // Default doctor (cardiology)
        $this->assertDatabaseHas('users', [
            'email' => 'doctor@demo.postvisit.ai',
            'role' => 'doctor',
        ]);

        // Specialty doctor (endocrinology)
        $this->assertDatabaseHas('users', [
            'email' => 'dr.patel@demo.postvisit.ai',
            'role' => 'doctor',
        ]);

        $this->assertEquals(2, User::where('role', 'doctor')->count());
    }
}
