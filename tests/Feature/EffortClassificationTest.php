<?php

namespace Tests\Feature;

use App\Enums\AiTier;
use App\Services\AI\AiTierManager;
use App\Services\AI\AnthropicClient;
use App\Services\AI\ClinicalReasoningPipeline;
use App\Services\AI\ContextAssembler;
use App\Services\AI\EscalationDetector;
use App\Services\AI\QaAssistant;
use Tests\TestCase;

class EffortClassificationTest extends TestCase
{
    private QaAssistant $qa;

    protected function setUp(): void
    {
        parent::setUp();

        // QaAssistant only needs its dependencies injected; classifyEffort() is pure logic
        $this->qa = new QaAssistant(
            $this->createMock(AnthropicClient::class),
            $this->createMock(ContextAssembler::class),
            $this->createMock(EscalationDetector::class),
            $this->createMock(AiTierManager::class),
            $this->createMock(ClinicalReasoningPipeline::class),
        );
    }

    public function test_low_effort_for_simple_factual_questions(): void
    {
        $this->assertEquals('low', $this->qa->classifyEffort('When is my next appointment?'));
        $this->assertEquals('low', $this->qa->classifyEffort('What is my diagnosis?'));
        $this->assertEquals('low', $this->qa->classifyEffort('Where is the clinic located?'));
        $this->assertEquals('low', $this->qa->classifyEffort('Who is my doctor?'));
        $this->assertEquals('low', $this->qa->classifyEffort('What time is my follow-up date?'));
        $this->assertEquals('low', $this->qa->classifyEffort('Which doctor should I contact?'));
    }

    public function test_medium_effort_is_default(): void
    {
        $this->assertEquals('medium', $this->qa->classifyEffort('Tell me about my condition'));
        $this->assertEquals('medium', $this->qa->classifyEffort('How should I manage my diet?'));
        $this->assertEquals('medium', $this->qa->classifyEffort('What lifestyle changes do you recommend?'));
        $this->assertEquals('medium', $this->qa->classifyEffort('Summarize my visit'));
    }

    public function test_high_effort_for_drug_safety_questions(): void
    {
        $this->assertEquals('high', $this->qa->classifyEffort('Are there any drug interactions I should know about?'));
        $this->assertEquals('high', $this->qa->classifyEffort('What are the side effects of metoprolol?'));
        $this->assertEquals('high', $this->qa->classifyEffort('Is it safe to exercise after taking my meds?'));
        $this->assertEquals('high', $this->qa->classifyEffort('Can I take ibuprofen with my blood thinner?'));
        $this->assertEquals('high', $this->qa->classifyEffort('What happens if I miss a dose?'));
        $this->assertEquals('high', $this->qa->classifyEffort('Should I stop taking the medication?'));
        $this->assertEquals('high', $this->qa->classifyEffort('Are there any contraindications?'));
        $this->assertEquals('high', $this->qa->classifyEffort('What if I take my medication combined with aspirin?'));
    }

    public function test_max_effort_for_escalation_patterns(): void
    {
        $this->assertEquals('max', $this->qa->classifyEffort('I have chest pain'));
        $this->assertEquals('max', $this->qa->classifyEffort('I can\'t breathe'));
        $this->assertEquals('max', $this->qa->classifyEffort('I took a double dose of my medication'));
        $this->assertEquals('max', $this->qa->classifyEffort('I think I may have overdosed'));
        $this->assertEquals('max', $this->qa->classifyEffort('I feel suicidal'));
        $this->assertEquals('max', $this->qa->classifyEffort('I have severe bleeding that won\'t stop'));
        $this->assertEquals('max', $this->qa->classifyEffort('I fainted after taking my pills'));
        $this->assertEquals('max', $this->qa->classifyEffort('I have sudden vision loss'));
    }

    public function test_max_effort_takes_priority_over_high(): void
    {
        // "chest pain" (max) should win over "side effect" (high)
        $this->assertEquals('max', $this->qa->classifyEffort('Is chest pain a side effect of my medication?'));
    }

    public function test_high_effort_takes_priority_over_low(): void
    {
        // "interaction" (high) should win over "what is" (low)
        $this->assertEquals('high', $this->qa->classifyEffort('What is the interaction between my medications?'));
    }

    public function test_effort_classification_is_case_insensitive(): void
    {
        $this->assertEquals('max', $this->qa->classifyEffort('I HAVE CHEST PAIN'));
        $this->assertEquals('high', $this->qa->classifyEffort('WHAT ARE THE SIDE EFFECTS?'));
        $this->assertEquals('low', $this->qa->classifyEffort('WHEN IS MY NEXT APPOINTMENT?'));
    }

    public function test_ai_tier_budget_for_effort_opus46(): void
    {
        $tier = AiTier::Opus46;

        $low = $tier->thinkingBudgetForEffort('low');
        $medium = $tier->thinkingBudgetForEffort('medium');
        $high = $tier->thinkingBudgetForEffort('high');
        $max = $tier->thinkingBudgetForEffort('max');

        $this->assertEquals(1024, $low['budget_tokens']);
        $this->assertEquals(4096, $low['max_tokens']);

        $this->assertEquals(4000, $medium['budget_tokens']);
        $this->assertEquals(8000, $medium['max_tokens']);

        $this->assertEquals(8000, $high['budget_tokens']);
        $this->assertEquals(16000, $high['max_tokens']);

        $this->assertEquals(16000, $max['budget_tokens']);
        $this->assertEquals(32000, $max['max_tokens']);
    }

    public function test_ai_tier_budget_for_effort_better(): void
    {
        $tier = AiTier::Better;

        $low = $tier->thinkingBudgetForEffort('low');
        $max = $tier->thinkingBudgetForEffort('max');

        $this->assertEquals(512, $low['budget_tokens']);
        $this->assertEquals(8000, $max['budget_tokens']);
    }

    public function test_ai_tier_budget_for_effort_good_returns_zero(): void
    {
        $tier = AiTier::Good;

        $result = $tier->thinkingBudgetForEffort('high');

        $this->assertEquals(0, $result['budget_tokens']);
        $this->assertEquals(4096, $result['max_tokens']);
    }
}
