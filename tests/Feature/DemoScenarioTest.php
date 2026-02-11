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
            ->assertJsonCount(9, 'data')
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
}
