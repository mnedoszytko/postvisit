<?php

namespace Tests\Feature;

use App\Services\AI\ToolExecutor;
use App\Services\Medications\OpenFdaClient;
use App\Services\Medications\RxNormClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ToolExecutorTest extends TestCase
{
    use RefreshDatabase;

    private ToolExecutor $executor;

    private OpenFdaClient $openFdaMock;

    private RxNormClient $rxNormMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->openFdaMock = Mockery::mock(OpenFdaClient::class);
        $this->rxNormMock = Mockery::mock(RxNormClient::class);

        $this->executor = new ToolExecutor($this->openFdaMock, $this->rxNormMock);
    }

    public function test_check_drug_interaction_returns_data(): void
    {
        $this->openFdaMock->shouldReceive('getCoReportedAdverseEvents')
            ->once()
            ->with('propranolol', 'aspirin', 10)
            ->andReturn([
                'events' => [
                    ['reaction' => 'Dizziness', 'count' => 150],
                    ['reaction' => 'Nausea', 'count' => 120],
                ],
                'total' => 270,
            ]);

        $this->openFdaMock->shouldReceive('getDrugLabel')
            ->twice()
            ->andReturn([
                'drug_interactions' => 'May increase hypotensive effect when combined.',
                'warnings' => 'Use with caution.',
            ]);

        $result = $this->executor->execute('check_drug_interaction', [
            'drug1' => 'propranolol',
            'drug2' => 'aspirin',
        ]);

        $this->assertEquals('propranolol', $result['drug1']);
        $this->assertEquals('aspirin', $result['drug2']);
        $this->assertCount(2, $result['co_reported_adverse_events']);
        $this->assertEquals(270, $result['co_reported_total']);
        $this->assertNotEmpty($result['label_interaction_warnings']);
        $this->assertEquals('OpenFDA (fda.gov)', $result['source']);
    }

    public function test_check_drug_interaction_requires_both_drugs(): void
    {
        $result = $this->executor->execute('check_drug_interaction', [
            'drug1' => 'propranolol',
        ]);

        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('required', $result['error']);
    }

    public function test_get_lab_reference_range_returns_data_for_known_test(): void
    {
        $result = $this->executor->execute('get_lab_reference_range', [
            'test_name' => 'glucose',
        ]);

        $this->assertEquals('glucose', $result['test_name']);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals('Fasting Blood Glucose', $result['data']['name']);
        $this->assertEquals('mg/dL', $result['data']['unit']);
        $this->assertEquals('Standard clinical reference ranges', $result['source']);
    }

    public function test_get_lab_reference_range_fuzzy_matches_by_name(): void
    {
        $result = $this->executor->execute('get_lab_reference_range', [
            'test_name' => 'HbA1c',
        ]);

        $this->assertArrayHasKey('data', $result);
        $this->assertEquals('Hemoglobin A1C', $result['data']['name']);
    }

    public function test_get_lab_reference_range_returns_error_for_unknown_test(): void
    {
        $result = $this->executor->execute('get_lab_reference_range', [
            'test_name' => 'completely_unknown_test_xyz',
        ]);

        $this->assertArrayHasKey('error', $result);
        $this->assertArrayHasKey('available_tests', $result);
        $this->assertNotEmpty($result['available_tests']);
    }

    public function test_get_drug_safety_info_returns_data(): void
    {
        $this->openFdaMock->shouldReceive('getDrugLabel')
            ->once()
            ->andReturn([
                'generic_name' => 'propranolol',
                'brand_name' => 'Inderal',
                'manufacturer' => 'Test Pharma',
                'warnings' => 'Do not stop abruptly.',
                'boxed_warning' => null,
                'adverse_reactions' => 'Bradycardia, fatigue, dizziness.',
                'drug_interactions' => 'Calcium channel blockers may increase effect.',
                'indications_and_usage' => 'Treatment of hypertension.',
                'dosage_and_administration' => 'Start with 40mg twice daily.',
                'information_for_patients' => 'Take with food.',
                'pregnancy' => 'Category C.',
                'nursing_mothers' => null,
            ]);

        $this->openFdaMock->shouldReceive('getAdverseEvents')
            ->once()
            ->andReturn([
                'events' => [
                    ['reaction' => 'Bradycardia', 'count' => 500],
                    ['reaction' => 'Fatigue', 'count' => 300],
                ],
                'total' => 800,
            ]);

        $result = $this->executor->execute('get_drug_safety_info', [
            'drug_name' => 'propranolol',
        ]);

        $this->assertEquals('propranolol', $result['drug_name']);
        $this->assertEquals('propranolol', $result['generic_name']);
        $this->assertEquals('Inderal', $result['brand_name']);
        $this->assertStringContainsString('Do not stop abruptly', $result['warnings']);
        $this->assertCount(2, $result['top_adverse_events']);
        $this->assertEquals('OpenFDA (fda.gov)', $result['source']);
    }

    public function test_get_drug_safety_info_handles_api_failure_gracefully(): void
    {
        $this->openFdaMock->shouldReceive('getDrugLabel')
            ->once()
            ->andThrow(new \RuntimeException('API timeout'));

        $result = $this->executor->execute('get_drug_safety_info', [
            'drug_name' => 'propranolol',
        ]);

        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('propranolol', $result['drug_name']);
        $this->assertEquals('OpenFDA (fda.gov)', $result['source']);
    }

    public function test_unknown_tool_returns_error(): void
    {
        $result = $this->executor->execute('nonexistent_tool', ['some' => 'input']);

        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Unknown tool', $result['error']);
    }

    public function test_get_lab_reference_range_returns_correct_data_for_tsh(): void
    {
        $result = $this->executor->execute('get_lab_reference_range', [
            'test_name' => 'tsh',
        ]);

        $this->assertArrayHasKey('data', $result);
        $this->assertEquals('Thyroid Stimulating Hormone', $result['data']['name']);
        $this->assertEquals('mIU/L', $result['data']['unit']);
        $this->assertEquals('0.4-4.0', $result['data']['normal_range']);
    }

    public function test_get_lab_reference_range_returns_correct_data_for_cholesterol(): void
    {
        $result = $this->executor->execute('get_lab_reference_range', [
            'test_name' => 'cholesterol_total',
        ]);

        $this->assertArrayHasKey('data', $result);
        $this->assertEquals('Total Cholesterol', $result['data']['name']);
        $this->assertEquals('<200', $result['data']['desirable']);
    }
}
