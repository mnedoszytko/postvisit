<?php

namespace Tests\Feature;

use App\Models\MedicalReference;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ReferenceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_list_references(): void
    {
        MedicalReference::create([
            'title' => '2023 ESC Guidelines for HF',
            'authors' => 'McDonagh TA et al.',
            'journal' => 'European Heart Journal',
            'year' => 2023,
            'doi' => '10.1093/eurheartj/ehad195',
            'pmid' => '37622666',
            'source_organization' => 'ESC',
            'category' => 'guideline',
            'specialty' => 'cardiology',
            'verified' => true,
            'verified_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/references');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.pmid', '37622666')
            ->assertJsonPath('data.0.doi', '10.1093/eurheartj/ehad195');
    }

    public function test_can_filter_references_by_specialty(): void
    {
        MedicalReference::create([
            'title' => 'Cardiology Guide',
            'year' => 2023,
            'category' => 'guideline',
            'specialty' => 'cardiology',
        ]);
        MedicalReference::create([
            'title' => 'Endocrinology Guide',
            'year' => 2023,
            'category' => 'guideline',
            'specialty' => 'endocrinology',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/references?specialty=cardiology');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Cardiology Guide');
    }

    public function test_can_filter_references_by_category(): void
    {
        MedicalReference::create([
            'title' => 'Guideline A',
            'year' => 2023,
            'category' => 'guideline',
            'specialty' => 'cardiology',
        ]);
        MedicalReference::create([
            'title' => 'Meta-analysis B',
            'year' => 2023,
            'category' => 'meta_analysis',
            'specialty' => 'cardiology',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/references?category=meta_analysis');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Meta-analysis B');
    }

    public function test_can_filter_verified_only(): void
    {
        MedicalReference::create([
            'title' => 'Verified Ref',
            'year' => 2023,
            'category' => 'guideline',
            'verified' => true,
            'verified_at' => now(),
        ]);
        MedicalReference::create([
            'title' => 'Unverified Ref',
            'year' => 2023,
            'category' => 'guideline',
            'verified' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/references?verified=1');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Verified Ref');
    }

    public function test_can_show_single_reference(): void
    {
        $ref = MedicalReference::create([
            'title' => '2023 ESC Guidelines',
            'authors' => 'McDonagh TA et al.',
            'journal' => 'European Heart Journal',
            'year' => 2023,
            'doi' => '10.1093/eurheartj/ehad195',
            'pmid' => '37622666',
            'url' => 'https://pubmed.ncbi.nlm.nih.gov/37622666/',
            'source_organization' => 'ESC',
            'category' => 'guideline',
            'specialty' => 'cardiology',
            'verified' => true,
            'verified_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/references/{$ref->id}");

        $response->assertOk()
            ->assertJsonPath('data.title', '2023 ESC Guidelines')
            ->assertJsonPath('data.pmid', '37622666')
            ->assertJsonPath('data.verified', true);
    }

    public function test_can_verify_pmid_via_pubmed(): void
    {
        Http::fake([
            'eutils.ncbi.nlm.nih.gov/*' => Http::response([
                'result' => [
                    '37622666' => [
                        'title' => 'ESC Guidelines for HF',
                        'authors' => [['name' => 'McDonagh TA'], ['name' => 'Metra M']],
                        'fulljournalname' => 'European Heart Journal',
                        'pubdate' => '2023 Oct',
                        'articleids' => [['idtype' => 'doi', 'value' => '10.1093/eurheartj/ehad195']],
                    ],
                ],
            ]),
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/references/verify-pmid', ['pmid' => '37622666']);

        $response->assertOk()
            ->assertJsonPath('data.exists', true)
            ->assertJsonPath('data.pmid', '37622666')
            ->assertJsonPath('data.journal', 'European Heart Journal');
    }

    public function test_verify_pmid_returns_false_for_invalid(): void
    {
        Http::fake([
            'eutils.ncbi.nlm.nih.gov/*' => Http::response([
                'result' => [
                    '99999999' => ['error' => 'cannot get document summary'],
                ],
            ]),
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/references/verify-pmid', ['pmid' => '99999999']);

        $response->assertOk()
            ->assertJsonPath('data.exists', false);
    }

    public function test_can_verify_reference_and_update_status(): void
    {
        Http::fake([
            'eutils.ncbi.nlm.nih.gov/*' => Http::response([
                'result' => [
                    '37622666' => [
                        'title' => 'ESC Guidelines for HF',
                        'authors' => [['name' => 'McDonagh TA']],
                        'fulljournalname' => 'European Heart Journal',
                        'pubdate' => '2023 Oct',
                        'articleids' => [['idtype' => 'doi', 'value' => '10.1093/eurheartj/ehad195']],
                    ],
                ],
            ]),
        ]);

        $ref = MedicalReference::create([
            'title' => 'Unverified ref',
            'year' => 2023,
            'pmid' => '37622666',
            'category' => 'guideline',
            'verified' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/references/{$ref->id}/verify");

        $response->assertOk()
            ->assertJsonPath('data.verified', true)
            ->assertJsonPath('data.reference.verified', true);

        $this->assertDatabaseHas('medical_references', [
            'id' => $ref->id,
            'verified' => true,
        ]);
    }

    public function test_references_require_authentication(): void
    {
        $response = $this->getJson('/api/v1/references');
        $response->assertStatus(401);
    }

    public function test_demo_seeder_creates_references(): void
    {
        $this->seed(\Database\Seeders\DemoSeeder::class);

        $this->assertDatabaseCount('medical_references', 8);
        $this->assertDatabaseHas('medical_references', [
            'pmid' => '37622666',
            'source_organization' => 'ESC',
            'verified' => true,
        ]);
        $this->assertDatabaseHas('medical_references', [
            'pmid' => '35363499',
            'source_organization' => 'AHA/ACC',
        ]);
    }

    public function test_pubmed_api_failure_returns_not_found(): void
    {
        Http::fake([
            'eutils.ncbi.nlm.nih.gov/*' => Http::response(null, 500),
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/references/verify-pmid', ['pmid' => '12345678']);

        $response->assertOk()
            ->assertJsonPath('data.exists', false);
    }
}
