<?php

namespace Tests\Feature;

use App\Models\Medication;
use App\Models\MedicationInteraction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MedicationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_can_search_medications_by_name(): void
    {
        Medication::factory()->create([
            'generic_name' => 'propranolol',
            'display_name' => 'Propranolol 40mg',
        ]);

        Medication::factory()->create([
            'generic_name' => 'metoprolol',
            'display_name' => 'Metoprolol 25mg',
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/medications/search?q=propra');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_can_search_medications_by_rxnorm_code(): void
    {
        $med = Medication::factory()->create(['rxnorm_code' => '8787']);

        $response = $this->actingAs($this->user)->getJson('/api/v1/medications/search?q=8787');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_search_requires_minimum_two_characters(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/medications/search?q=a');

        $response->assertStatus(422)
            ->assertJsonValidationErrors('q');
    }

    public function test_search_requires_query_parameter(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/medications/search');

        $response->assertStatus(422)
            ->assertJsonValidationErrors('q');
    }

    public function test_can_view_medication_by_rxnorm_code(): void
    {
        $med = Medication::factory()->create(['rxnorm_code' => '12345']);

        $response = $this->actingAs($this->user)->getJson('/api/v1/medications/12345');

        $response->assertOk()
            ->assertJsonPath('data.rxnorm_code', '12345');
    }

    public function test_medication_not_found_returns_404(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/medications/99999');

        $response->assertStatus(404);
    }

    public function test_can_view_medication_interactions(): void
    {
        $medA = Medication::factory()->create(['rxnorm_code' => '111']);
        $medB = Medication::factory()->create(['rxnorm_code' => '222']);

        MedicationInteraction::factory()->create([
            'drug_a_id' => $medA->id,
            'drug_b_id' => $medB->id,
            'severity' => 'major',
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/medications/111/interactions');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_medications_require_authentication(): void
    {
        $response = $this->getJson('/api/v1/medications/search?q=test');

        $response->assertStatus(401);
    }
}
