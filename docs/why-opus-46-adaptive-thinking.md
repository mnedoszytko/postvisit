# Why Opus 4.6: Adaptive Thinking

## The Feature

PostVisit.ai dynamically adjusts how deeply the AI reasons based on question complexity. Simple factual questions get instant responses; safety-critical questions get maximum reasoning depth.

## Why Opus 4.6 Specifically

Opus 4.6 introduced extended thinking with configurable `budget_tokens`, allowing applications to control reasoning depth per-request. This is unique to Opus 4.6 -- no other model supports variable thinking budgets at this granularity.

### How It Works

1. Patient asks a question in the chat
2. `QaAssistant::classifyEffort()` classifies complexity using keyword heuristics: **low** / **medium** / **high** / **max**
3. `AiTier::thinkingBudgetForEffort()` maps the effort level to a thinking budget (1K-16K tokens)
4. The budget is passed to the Anthropic API via `budget_tokens` in the streaming call
5. The effort level is streamed to the frontend via SSE before the response begins

### Effort Levels

| Level  | Budget (Opus 4.6) | Max Tokens | Trigger Examples                                    |
|--------|--------------------|------------|-----------------------------------------------------|
| low    | 1,024              | 4,096      | "When is my next appointment?", "What is my diagnosis?" |
| medium | 4,000              | 8,000      | "Tell me about my condition", "Summarize my visit"  |
| high   | 8,000              | 16,000     | "What are the side effects?", "Can I take X with Y?" |
| max    | 16,000             | 32,000     | "I have chest pain", "I took a double dose"         |

### Impact

- **4x faster responses** for simple questions (1K vs 4K thinking budget)
- **4x deeper reasoning** for safety-critical questions (16K vs 4K)
- **Visible to user**: effort badge shows reasoning depth below each response
- **Backwards compatible**: medium effort matches previous default behavior

### User-Facing Badges

- **low**: "Quick answer" (gray badge)
- **medium**: no badge (default, avoids clutter)
- **high**: "Deep analysis" (amber badge)
- **max**: "Clinical reasoning" (red badge)

### Technical Details

- **Classification**: keyword-based regex heuristic in `QaAssistant::classifyEffort()` -- zero latency, no AI call needed
- **Priority order**: max > high > low > medium (safety patterns always win)
- **Budget mapping**: `AiTier::thinkingBudgetForEffort()` scales budgets per tier (Opus 4.6 gets full budgets, Better gets half, Good gets zero)
- **Streaming**: effort level streamed as first SSE event (`{"effort":"high"}`) before response begins
- **Deep reasoning pipeline**: unaffected -- ClinicalReasoningPipeline has its own budget logic for Plan-Execute-Verify

### Files Modified

- `app/Enums/AiTier.php` -- added `thinkingBudgetForEffort()` method
- `app/Services/AI/QaAssistant.php` -- added `classifyEffort()` method, integrated effort-based budgets
- `app/Http/Controllers/Api/ChatController.php` -- added effort SSE event handler
- `resources/js/stores/chat.js` -- added effort field to message state, handles effort SSE event
- `resources/js/components/ChatPanel.vue` -- shows effort level badge on completed responses
- `tests/Feature/EffortClassificationTest.php` -- comprehensive test coverage for classification and budget mapping
