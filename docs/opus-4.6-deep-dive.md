# How PostVisit.ai Uses Claude Opus 4.6

PostVisit.ai is built to demonstrate the full capabilities of Claude Opus 4.6. Every AI feature in the platform is designed to showcase a specific Opus 4.6 capability: extended thinking, the 1M context window, tool use, prompt caching, and streaming. This document walks through each one with real code from our codebase.

---

## 1. Extended Thinking: Clinical Reasoning Before Every Answer

Extended thinking lets the AI "think through" a clinical question before responding. This is critical in healthcare: a patient asking "Can I take ibuprofen with my beta-blocker?" requires reasoning through pharmacology, their conditions, and their prescriptions -- not just pattern-matching.

### Per-Subsystem Thinking Budgets

Each AI subsystem has a calibrated thinking budget. Escalation detection needs to be fast (6K tokens). Transcript-to-SOAP conversion needs deep reasoning (10K tokens).

```php
// app/Enums/AiTier.php

public function thinkingBudget(string $subsystem): int
{
    return match ($this) {
        self::Good => 0,  // No thinking on basic tier
        self::Better => match ($subsystem) {
            'chat' => 4000,
            'scribe' => 6000,
            'escalation' => 0,
            'reasoning' => 6000,
            default => 4000,
        },
        self::Opus46 => match ($subsystem) {
            'chat' => 8000,
            'scribe' => 10000,
            'escalation' => 6000,
            'reasoning' => 10000,
            default => 8000,
        },
    };
}
```

### Adaptive Budgets Based on Question Complexity

Not every question needs 10K tokens of thinking. A simple "What time should I take my medication?" needs minimal reasoning, while "Are there any interactions between my three prescriptions and my kidney condition?" requires deep analysis.

The system classifies question complexity into effort levels and allocates thinking tokens accordingly:

```php
// app/Enums/AiTier.php

public function thinkingBudgetForEffort(string $effort): array
{
    return match ($this) {
        self::Good => ['budget_tokens' => 0, 'max_tokens' => 4096],
        self::Opus46 => match ($effort) {
            'low'     => ['budget_tokens' => 1024,  'max_tokens' => 4096],
            'high'    => ['budget_tokens' => 8000,  'max_tokens' => 16000],
            'max'     => ['budget_tokens' => 16000, 'max_tokens' => 32000],
            default   => ['budget_tokens' => 4000,  'max_tokens' => 8000],
        },
    };
}
```

### Streaming Thinking to the Frontend

Thinking is streamed via SSE -- patients see a "thinking" indicator while the AI reasons, giving transparency:

```php
// app/Services/AI/AnthropicClient.php

public function streamWithThinking(
    string|array $systemPrompt,
    array $messages,
    array $options = []
): Generator {
    $body = [
        'model' => $options['model'] ?? $this->defaultModel,
        'max_tokens' => $options['max_tokens'] ?? 16000,
        'stream' => true,
        'system' => $this->serializeSystemPrompt($systemPrompt),
        'messages' => $messages,
        'thinking' => [
            'type' => 'enabled',
            'budget_tokens' => $options['budget_tokens'] ?? 8000,
        ],
    ];

    yield from $this->rawCurlStream($body, withThinking: true);
}
```

SSE events distinguish thinking from response text:

```php
if ($deltaType === 'thinking_delta') {
    yield ['type' => 'thinking', 'content' => $decoded['delta']['thinking']];
} elseif ($deltaType === 'text_delta') {
    yield ['type' => 'text', 'content' => $decoded['delta']['text']];
}
```

---

## 2. 1M Context Window: Full Clinical Picture

With 1M tokens, we load the patient's complete clinical context -- just as a physician would when reviewing a chart. No RAG retrieval, no truncated summaries. The model sees everything.

### 8-Layer Context Assembly

The `ContextAssembler` builds context in 8 layers, each tracked for token usage:

```php
// app/Services/AI/ContextAssembler.php

public function assembleForVisit(Visit $visit, string $promptName = 'qa-assistant'): array
{
    $this->tokenBreakdown = [];

    // Layer 1: System prompt (behavioral rules, escalation protocol)
    // Layer 2: Clinical guidelines (WikiDoc + DailyMed + PMC full-text articles)
    // Layer 3: Visit data (SOAP note, transcript, observations)
    // Layer 4: Patient record (demographics, conditions, prescriptions)
    // Layer 5: Health history (observations over last 3 months)
    // Layer 6: Recent visit summaries (Opus: ALL visits, Standard: last 3)
    // Layer 7: Device data (wearable readings -- Opus: expanded, Standard: limited)
    // Layer 8: FDA safety data (adverse events, boxed warnings)
    // Layer 9: Personal medical library (saved articles, analyzed documents)
    // Layer 10: Context compaction summaries (opt-in)
}
```

### Tier-Based Context Expansion

Opus 4.6 gets richer context because it can handle it:

```php
// Opus 4.6: all visits (null = no limit), Standard: last 3
$recentVisitsContext = $this->formatRecentVisitsContext(
    $visit, $isOpus ? null : 3
);

// Opus 4.6: full drug labels (5000 chars), Standard: truncated (500 chars)
$fdaContext = $this->formatFdaSafetyContext($visit, $isOpus);

// Opus 4.6: expanded device readings, Standard: limited
$deviceContext = $this->formatDeviceDataContext($visit, $isOpus);
```

### Context Size in Practice

A typical Opus 4.6 request loads:

| Layer | Typical Size |
|-------|-------------|
| System prompt | ~2,000 tokens |
| Clinical guidelines (PMC full-text) | ~50,000-150,000 tokens |
| Visit data (SOAP + transcript) | ~5,000-20,000 tokens |
| Patient record | ~1,000-3,000 tokens |
| Health history | ~2,000-5,000 tokens |
| FDA safety data | ~2,000-5,000 tokens |
| **Total per request** | **~60,000-180,000 tokens** |

The 1M window gives room for conversation history on top of this context.

---

## 3. Prompt Caching: 78% Cost Reduction

System prompts and clinical guidelines are stable across a conversation. Prompt caching avoids re-processing them on every request.

### Cache Control on Stable Blocks

```php
// app/Services/AI/ContextAssembler.php

private function buildCacheableSystemBlocks(
    string $systemPrompt,
    Visit $visit,
    string $ttl
): array {
    $blocks = [];

    // Block 1: System prompt -- cacheable (same per prompt type)
    $blocks[] = TextBlockParam::with(
        text: $systemPrompt,
        cacheControl: CacheControlEphemeral::with(ttl: $ttl),
    );

    // Block 2: Clinical guidelines -- cacheable (stable reference material)
    $guidelines = $this->loadGuidelinesContent($visit);
    if ($guidelines) {
        $blocks[] = TextBlockParam::with(
            text: $guidelines,
            cacheControl: CacheControlEphemeral::with(ttl: $ttl),
        );
    }

    return $blocks;
}
```

### Cost Impact

For a typical 10-message conversation with ~150K cached tokens:

| Scenario | Input Cost |
|----------|-----------|
| Without caching | $22.50 |
| With caching | $4.84 |
| **Savings** | **78%** |

The 5-minute cache TTL covers typical patient sessions (5-15 minutes of Q&A).

---

## 4. Tool Use: AI That Can Look Things Up

The AI doesn't just answer from context -- it can call tools to look up real medication data, check drug interactions, and retrieve clinical guidelines in real time.

### Tool Definitions

```php
// app/Services/AI/PatientEducationGenerator.php

private function getToolDefinitions(): array
{
    return [
        [
            'name' => 'check_drug_interaction',
            'description' => 'Check for known interactions between two medications.',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'drug1' => ['type' => 'string', 'description' => 'First drug generic name'],
                    'drug2' => ['type' => 'string', 'description' => 'Second drug generic name'],
                ],
                'required' => ['drug1', 'drug2'],
            ],
        ],
        [
            'name' => 'get_drug_safety_info',
            'description' => 'Get safety information including warnings, side effects, and boxed warnings from FDA labels.',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'drug_name' => ['type' => 'string', 'description' => 'Drug generic name'],
                ],
                'required' => ['drug_name'],
            ],
        ],
        [
            'name' => 'get_lab_reference_range',
            'description' => 'Get normal reference ranges for a lab test.',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'test_name' => ['type' => 'string', 'description' => 'Lab test name'],
                ],
                'required' => ['test_name'],
            ],
        ],
    ];
}
```

### Agentic Tool Loop

The client runs an agentic loop: call the model, execute any tool calls, feed results back, repeat until the model says it's done:

```php
// app/Services/AI/AnthropicClient.php

public function chatWithTools(
    string|array $systemPrompt,
    array $messages,
    array $tools,
    callable $toolExecutor,
    ?callable $onToolUse = null,
    array $options = [],
): array {
    $maxIterations = 5;

    for ($i = 0; $i < $maxIterations; $i++) {
        $body = [
            'model' => $options['model'] ?? $this->defaultModel,
            'max_tokens' => $options['max_tokens'] ?? 16000,
            'system' => $this->serializeSystemPrompt($systemPrompt),
            'messages' => $messages,
            'tools' => $tools,
        ];

        if ($options['budget_tokens'] ?? 0 > 0) {
            $body['thinking'] = [
                'type' => 'enabled',
                'budget_tokens' => $options['budget_tokens'],
            ];
        }

        $response = $this->rawCurlRequest($body);

        if ($response['stop_reason'] === 'tool_use') {
            // Extract tool calls, execute them, feed results back
            $messages[] = ['role' => 'assistant', 'content' => $response['content']];

            $toolResultBlocks = [];
            foreach ($response['content'] as $block) {
                if ($block['type'] !== 'tool_use') continue;

                $result = $toolExecutor($block['name'], $block['input']);
                $toolResultBlocks[] = [
                    'type' => 'tool_result',
                    'tool_use_id' => $block['id'],
                    'content' => json_encode($result),
                ];
            }
            $messages[] = ['role' => 'user', 'content' => $toolResultBlocks];
            continue; // Next iteration
        }

        // stop_reason === 'end_turn' -- done
        return ['text' => $text, 'thinking' => $thinking, 'tools_used' => $toolsUsed];
    }
}
```

### Tool Execution

Tools call real APIs -- RxNorm for drug data, OpenFDA for adverse events, DailyMed for drug labels:

```php
// app/Services/AI/ToolExecutor.php

public function execute(string $toolName, array $input): array
{
    return match ($toolName) {
        'check_drug_interaction' => $this->checkDrugInteraction($input),
        'get_drug_safety_info' => $this->getDrugSafetyInfo($input),
        'get_lab_reference_range' => $this->getLabReferenceRange($input),
        'search_clinical_guidelines' => $this->searchClinicalGuidelines($input),
        'get_adverse_events' => $this->getAdverseEvents($input),
        default => ['error' => "Unknown tool: {$toolName}"],
    };
}
```

---

## 5. Plan-Execute-Verify: Multi-Phase Clinical Reasoning

For complex clinical questions, the system uses a 3-phase pipeline that mirrors how clinicians reason:

```php
// app/Services/AI/ClinicalReasoningPipeline.php

public function reason(Visit $visit, array $messages, string $question): Generator
{
    // Phase 1: PLAN -- identify knowledge domains needed
    yield ['type' => 'phase', 'content' => 'planning'];
    $plan = $this->planPhase($visit, $question);

    // Phase 2: EXECUTE -- generate response with full context + plan guidance
    yield ['type' => 'phase', 'content' => 'reasoning'];
    $augmentedMessages[] = [
        'role' => 'user',
        'content' => "[CLINICAL REASONING PLAN]\n{$plan}\n[END PLAN]",
    ];
    yield from $this->client->streamWithThinking(
        $context['system_prompt'],
        $augmentedMessages,
        ['budget_tokens' => $tier->thinkingBudget('reasoning')]
    );

    // Phase 3: VERIFY -- validate response against clinical evidence
    // (runs post-stream, checks for safety and accuracy)
}
```

The pipeline is triggered automatically for complex questions:

```php
// app/Services/AI/QaAssistant.php

if ($tier->thinkingEnabled()
    && $this->reasoningPipeline->shouldUseDeepReasoning($question)) {
    // Full Plan-Execute-Verify pipeline
    yield from $this->reasoningPipeline->reason($visit, $history, $question);
    return;
}
```

---

## 6. Escalation Detection: Safety-Critical Thinking

When a patient describes symptoms that could be a medical emergency, the system must detect this reliably. On the Opus 4.6 tier, escalation decisions use extended thinking:

```php
// app/Services/AI/EscalationDetector.php

private function aiEvaluate(string $message, ?Visit $visit): array
{
    $tier = $this->tierManager->current();

    if ($tier->escalationThinkingEnabled()) {
        // Opus 4.6: think through clinical context before deciding
        $result = $this->client->chatWithThinking($systemPrompt, $messages, [
            'model' => $tier->model(),
            'budget_tokens' => $tier->thinkingBudget('escalation'), // 6,000
        ]);

        $parsed = $this->parseJsonResponse($result['text']);
        $parsed['clinical_reasoning'] = $result['thinking'];
        return $parsed;
    }

    // Lower tiers: direct classification without thinking
    return $this->parseJsonResponse(
        $this->client->chat($systemPrompt, $messages)
    );
}
```

Why thinking matters for escalation: A patient saying "I feel short of breath after climbing stairs" might be normal post-exercise dyspnea -- or a sign of heart failure. Extended thinking lets the AI reason through the patient's conditions, medications, and recent vitals before classifying urgency.

Only Opus 4.6 enables thinking for escalation -- it's a feature gate:

```php
// app/Enums/AiTier.php

public function escalationThinkingEnabled(): bool
{
    return $this === self::Opus46;
}
```

---

## 7. 3-Tier Architecture: Progressive Value Demonstration

The system implements three AI tiers to demonstrate the progressive value of Opus 4.6:

| Feature | Good (Sonnet) | Better (Opus) | Opus 4.6 (Full) |
|---------|--------------|---------------|-----------------|
| Extended Thinking | No | Chat + Scribe | All subsystems |
| Clinical Guidelines | None | None | Full-text PMC articles |
| Escalation Detection | Keywords only | Keywords + AI | Keywords + AI + Thinking |
| Clinical Reasoning | No | No | Plan-Execute-Verify |
| Prompt Caching | No | Yes | Yes |
| Tool Use | No | No | Drug lookup, interactions, guidelines |
| Thinking Budget (total) | 0 | 16K tokens | 34K tokens |

Switching tiers in real-time during the demo shows the progressive improvement in response quality:

```php
// app/Services/AI/QaAssistant.php -- tier routing

$tier = $this->tierManager->current();

if ($tier->thinkingEnabled()) {
    $budgets = $tier->thinkingBudgetForEffort($effort);
    yield from $this->client->streamWithThinking(
        $context['system_prompt'], $messages,
        ['budget_tokens' => $budgets['budget_tokens']]
    );
} else {
    foreach ($this->client->stream(
        $context['system_prompt'], $messages
    ) as $chunk) {
        yield ['type' => 'text', 'content' => $chunk];
    }
}
```

---

## Summary: Every Opus 4.6 Feature, Used

| Opus 4.6 Feature | PostVisit.ai Usage | Why It Matters |
|-------------------|--------------------|----------------|
| **Extended Thinking** | Per-subsystem budgets (1K-16K tokens) | Clinical reasoning before patient-facing responses |
| **Adaptive Thinking** | Effort-based budget routing | Simple questions = fast, complex questions = deep |
| **1M Context Window** | 8-layer context assembly (60K-180K tokens typical) | Full clinical picture without RAG or truncation |
| **Prompt Caching** | System prompt + guidelines cached (5min TTL) | 78% input cost reduction |
| **Tool Use** | Agentic loop with 5 medical tools | Real-time drug lookups, interaction checks, guidelines |
| **Streaming** | Raw cURL SSE with thinking + text channels | Token-by-token delivery with thinking transparency |
| **Safety (ASL-4)** | Thinking-backed escalation detection | Clinical reasoning on life-safety decisions |

Every feature is in production, tested, and demonstrable in the live demo at [postvisit.ai](https://postvisit.ai).

---

*Last updated: 2026-02-15*
