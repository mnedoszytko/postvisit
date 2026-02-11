<?php

namespace Tests\Feature;

use App\Models\Observation;
use App\Models\Patient;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DemoSeeder::class);
    }

    public function test_seeder_creates_hf_biomarkers(): void
    {
        $patient = Patient::where('email', 'patient@demo.postvisit.ai')->first();

        // BNP
        $bnp = Observation::where('patient_id', $patient->id)
            ->where('code', '30934-4')->first();
        $this->assertNotNull($bnp);
        $this->assertEquals(450, (float) $bnp->value_quantity);
        $this->assertEquals('H', $bnp->interpretation);
        $this->assertArrayHasKey('hf_threshold', $bnp->specialty_data);

        // NT-proBNP
        $ntprobnp = Observation::where('patient_id', $patient->id)
            ->where('code', '33762-6')->first();
        $this->assertNotNull($ntprobnp);
        $this->assertEquals(1850, (float) $ntprobnp->value_quantity);
        $this->assertEquals('H', $ntprobnp->interpretation);
    }

    public function test_seeder_creates_renal_function_labs(): void
    {
        $patient = Patient::where('email', 'patient@demo.postvisit.ai')->first();

        // Creatinine
        $creat = Observation::where('patient_id', $patient->id)
            ->where('code', '2160-0')->first();
        $this->assertNotNull($creat);
        $this->assertEquals(1.4, (float) $creat->value_quantity);
        $this->assertEquals('H', $creat->interpretation);
        $this->assertArrayHasKey('egfr_estimated', $creat->specialty_data);

        // BUN
        $bun = Observation::where('patient_id', $patient->id)
            ->where('code', '3094-0')->first();
        $this->assertNotNull($bun);
        $this->assertEquals(28, (float) $bun->value_quantity);
        $this->assertEquals('H', $bun->interpretation);
    }

    public function test_seeder_creates_electrolytes(): void
    {
        $patient = Patient::where('email', 'patient@demo.postvisit.ai')->first();

        // Sodium (hyponatremia in HF)
        $sodium = Observation::where('patient_id', $patient->id)
            ->where('code', '2951-2')->first();
        $this->assertNotNull($sodium);
        $this->assertEquals(134, (float) $sodium->value_quantity);
        $this->assertEquals('L', $sodium->interpretation);

        // Potassium (existing)
        $potassium = Observation::where('patient_id', $patient->id)
            ->where('code', '2823-3')->first();
        $this->assertNotNull($potassium);
        $this->assertEquals('N', $potassium->interpretation);
    }

    public function test_seeder_creates_lipid_panel(): void
    {
        $patient = Patient::where('email', 'patient@demo.postvisit.ai')->first();

        $codes = ['2093-3', '2089-1', '2085-9', '2571-8'];
        foreach ($codes as $code) {
            $obs = Observation::where('patient_id', $patient->id)
                ->where('code', $code)->first();
            $this->assertNotNull($obs, "Lipid panel observation {$code} not found");
            $this->assertEquals('laboratory', $obs->category);
        }
    }

    public function test_seeder_creates_hemoglobin(): void
    {
        $patient = Patient::where('email', 'patient@demo.postvisit.ai')->first();

        $hgb = Observation::where('patient_id', $patient->id)
            ->where('code', '718-7')->first();
        $this->assertNotNull($hgb);
        $this->assertEquals(12.8, (float) $hgb->value_quantity);
        $this->assertEquals('L', $hgb->interpretation);
        $this->assertArrayHasKey('clinical_significance', $hgb->specialty_data);
    }

    public function test_seeder_total_observation_count(): void
    {
        $patient = Patient::where('email', 'patient@demo.postvisit.ai')->first();

        // 7 original + 9 HF labs + 10 weight + 9 BP series + 18 longitudinal (9 HR + 9 BP home) = 53
        $count = Observation::where('patient_id', $patient->id)->count();
        $this->assertEquals(53, $count);
    }

    public function test_hf_labs_categorized_as_laboratory(): void
    {
        $patient = Patient::where('email', 'patient@demo.postvisit.ai')->first();

        $labCodes = ['30934-4', '33762-6', '2160-0', '3094-0', '2951-2', '2089-1', '2085-9', '2571-8', '718-7'];

        foreach ($labCodes as $code) {
            $obs = Observation::where('patient_id', $patient->id)
                ->where('code', $code)->first();
            $this->assertEquals('laboratory', $obs->category, "Observation {$code} should be categorized as laboratory");
        }
    }
}
