<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MedicalLookupTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_search_conditions_via_nih(): void
    {
        Http::fake([
            'clinicaltables.nlm.nih.gov/*' => Http::response([
                3,
                ['I10', 'I11.0', 'I11.9'],
                null,
                [
                    ['I10', 'Essential (primary) hypertension'],
                    ['I11.0', 'Hypertensive heart disease with heart failure'],
                    ['I11.9', 'Hypertensive heart disease without heart failure'],
                ],
            ]),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/lookup/conditions?q=hypertension');

        $response->assertOk()
            ->assertJsonPath('data.total', 3)
            ->assertJsonCount(3, 'data.matches')
            ->assertJsonPath('data.matches.0.code', 'I10');
    }

    public function test_can_search_drugs_via_nih(): void
    {
        Http::fake([
            'clinicaltables.nlm.nih.gov/*' => Http::response([
                2,
                ['Propranolol', 'Propranolol HCl'],
                [null, null],
            ]),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/lookup/drugs?q=propranolol');

        $response->assertOk()
            ->assertJsonPath('data.total', 2)
            ->assertJsonCount(2, 'data.matches');
    }

    public function test_can_search_procedures_via_nih(): void
    {
        Http::fake([
            'clinicaltables.nlm.nih.gov/*' => Http::response([
                1,
                ['Echocardiography'],
            ]),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/lookup/procedures?q=echo');

        $response->assertOk()
            ->assertJsonPath('data.total', 1);
    }

    public function test_can_get_drug_label_via_dailymed(): void
    {
        Http::fake([
            'dailymed.nlm.nih.gov/*/spls.json*' => Http::response([
                'data' => [
                    ['setid' => 'abc-123', 'title' => 'PROPRANOLOL'],
                ],
            ]),
            'dailymed.nlm.nih.gov/*/spls/abc-123.json' => Http::response([
                'setid' => 'abc-123',
                'title' => 'PROPRANOLOL HYDROCHLORIDE tablet',
                'effective_time' => '20230101',
                'author' => 'Test Pharma',
            ]),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/lookup/drug-label?drug_name=propranolol');

        $response->assertOk()
            ->assertJsonPath('data.setid', 'abc-123')
            ->assertJsonPath('data.title', 'PROPRANOLOL HYDROCHLORIDE tablet');
    }

    public function test_condition_search_requires_min_two_chars(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/lookup/conditions?q=a');

        $response->assertStatus(422);
    }

    public function test_drug_search_requires_query(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/lookup/drugs');

        $response->assertStatus(422);
    }

    public function test_lookup_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/lookup/conditions?q=test');

        $response->assertStatus(401);
    }

    public function test_nih_api_failure_returns_empty_results(): void
    {
        Http::fake([
            'clinicaltables.nlm.nih.gov/*' => Http::response(null, 500),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/lookup/conditions?q=hypertension');

        $response->assertOk()
            ->assertJsonPath('data.total', 0)
            ->assertJsonPath('data.matches', []);
    }

    public function test_dailymed_api_failure_returns_empty(): void
    {
        Http::fake([
            'dailymed.nlm.nih.gov/*' => Http::response(null, 500),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/lookup/drug-label?drug_name=propranolol');

        $response->assertOk()
            ->assertJsonPath('data', []);
    }
}
