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

    public function test_triggers_deep_reasoning_for_drug_safety_questions(): void
    {
        $this->assertTrue($this->pipeline->shouldUseDeepReasoning('What are the side effects of propranolol?'));
        $this->assertTrue($this->pipeline->shouldUseDeepReasoning('Can I take ibuprofen with my medication?'));
        $this->assertTrue($this->pipeline->shouldUseDeepReasoning('Is it safe to drink alcohol?'));
        $this->assertTrue($this->pipeline->shouldUseDeepReasoning('What if I miss a dose?'));
    }

    public function test_triggers_deep_reasoning_for_dosage_questions(): void
    {
        $this->assertTrue($this->pipeline->shouldUseDeepReasoning('What is the correct dosage?'));
        $this->assertTrue($this->pipeline->shouldUseDeepReasoning('Should I take it with food?'));
        $this->assertTrue($this->pipeline->shouldUseDeepReasoning('When to take my medication?'));
    }

    public function test_triggers_deep_reasoning_for_symptom_combination_questions(): void
    {
        $this->assertTrue($this->pipeline->shouldUseDeepReasoning('I have a new symptom since starting the medication'));
        $this->assertTrue($this->pipeline->shouldUseDeepReasoning('My condition is getting worse'));
        $this->assertTrue($this->pipeline->shouldUseDeepReasoning('Should I stop taking propranolol?'));
    }

    public function test_does_not_trigger_for_simple_questions(): void
    {
        $this->assertFalse($this->pipeline->shouldUseDeepReasoning('What time is my next appointment?'));
        $this->assertFalse($this->pipeline->shouldUseDeepReasoning('Who was my doctor?'));
        $this->assertFalse($this->pipeline->shouldUseDeepReasoning('Thank you for the help'));
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
