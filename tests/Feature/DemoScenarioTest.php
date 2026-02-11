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
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.key', 'pvcs')
            ->assertJsonPath('data.0.name', 'PVCs / Palpitations')
            ->assertJsonPath('data.0.patient_name', 'Alex Johnson')
            ->assertJsonPath('data.1.key', 'heart-failure')
            ->assertJsonPath('data.1.name', 'Heart Failure')
            ->assertJsonPath('data.1.patient_name', 'Maria Santos');
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

    public function test_can_start_heart_failure_scenario(): void
    {
        $response = $this->postJson('/api/v1/demo/start-scenario', [
            'scenario' => 'heart-failure',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.user.role', 'patient')
            ->assertJsonPath('data.scenario', 'heart-failure');

        $this->assertDatabaseHas('patients', [
            'first_name' => 'Maria',
            'last_name' => 'Santos',
        ]);

        $this->assertDatabaseHas('conditions', [
            'code' => 'I50.22',
            'code_display' => 'Chronic systolic heart failure',
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
        $this->postJson('/api/v1/demo/start-scenario', ['scenario' => 'heart-failure']);

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
