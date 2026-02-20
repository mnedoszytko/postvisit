<?php

namespace Tests\Feature;

use App\Enums\AiTier;
use App\Services\AI\ClinicalReasoningPipeline;
use Tests\TestCase;

class ClinicalReasoningPipelineTest extends TestCase
{
    private ClinicalReasoningPipeline $pipeline;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pipeline = $this->app->make(ClinicalReasoningPipeline::class);
    }

    public function test_deep_reasoning_disabled_for_drug_safety_questions(): void
    {
        // Deep reasoning disabled for post-judging public access — all return false
        $this->assertFalse($this->pipeline->shouldUseDeepReasoning('What are the side effects of propranolol?'));
        $this->assertFalse($this->pipeline->shouldUseDeepReasoning('Can I take ibuprofen with my medication?'));
        $this->assertFalse($this->pipeline->shouldUseDeepReasoning('Is it safe to drink alcohol?'));
        $this->assertFalse($this->pipeline->shouldUseDeepReasoning('What if I miss a dose?'));
    }

    public function test_deep_reasoning_disabled_for_all_questions(): void
    {
        $this->assertFalse($this->pipeline->shouldUseDeepReasoning('What is the correct dosage?'));
        $this->assertFalse($this->pipeline->shouldUseDeepReasoning('I have a new symptom since starting the medication'));
        $this->assertFalse($this->pipeline->shouldUseDeepReasoning('What time is my next appointment?'));
        $this->assertFalse($this->pipeline->shouldUseDeepReasoning('Hello'));
    }

    public function test_reasoning_budget_is_available_in_tier(): void
    {
        $this->assertEquals(3000, AiTier::Opus46->thinkingBudget('reasoning'));
        $this->assertEquals(2000, AiTier::Better->thinkingBudget('reasoning'));
        $this->assertEquals(0, AiTier::Good->thinkingBudget('reasoning'));
    }

    public function test_opus46_tier_includes_reasoning_feature(): void
    {
        $features = AiTier::Opus46->features();

        $this->assertContains('Multi-step clinical reasoning (Plan-Execute-Verify)', $features);
    }
}
