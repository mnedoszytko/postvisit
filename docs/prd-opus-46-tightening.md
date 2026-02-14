# PRD: Tightening Opus 4.6 Utilization in PostVisit.ai

**Status**: Draft — awaiting review
**Author**: Agent 4
**Date**: 2026-02-14
**Priority**: High — hackathon deadline Feb 16, 15:00 EST
**Risk Level**: Medium — changes to AI pipeline, but backwards-compatible

---

## Executive Summary

PostVisit.ai already uses Opus 4.6 well: extended thinking, prompt caching, 7-layer context assembly, 3-tier comparison, SSE streaming with thinking visibility. But we're leaving significant Opus 4.6 capabilities on the table.

This PRD maps 5 concrete upgrades from research → implementation plan, scoped to what's achievable in ~24h before deadline, and ordered by impact-to-effort ratio.

---

## Current State vs. Potential

| Capability | Current Usage | Potential | Gap |
|-----------|--------------|-----------|-----|
| **Adaptive Thinking** | Fixed `budget_tokens` per subsystem (4K-10K) via old `ThinkingConfigEnabled` API | Dynamic effort levels (low/medium/high/max) routed by question complexity | Using static budgets instead of model-adaptive thinking |
| **1M Context Window** | ~80-100K tokens loaded (with PMC articles) | 250-500K tokens possible with full medical records, multi-visit history, comprehensive guidelines | Using ~10% of available context |
| **128K Output** | `max_tokens: 16000` (max) | Up to 128K output for comprehensive patient education documents | Using 12.5% of output capacity |
| **Tool Use (function calling)** | Zero — all structured output via JSON prompting | Native tool definitions for drug interactions, guideline lookup, lab references | Missing platform feature that judges know exists |
| **Context Compaction** | Not implemented | Auto-compression for multi-session longitudinal care | No cross-session memory |

---

## Upgrade 1: Adaptive Thinking with Effort Levels

**Impact**: High | **Effort**: Low (2-3h) | **Risk**: Low
**Priority**: P0 — do first

### What Changes

Replace static `budget_tokens` with Opus 4.6's adaptive thinking API. Route question complexity to appropriate effort levels.

### Current Implementation

```php
// AnthropicClient.php line 80
ThinkingConfigEnabled::with(budgetTokens: $budgetTokens)

// rawCurlStream body, line 148
'thinking' => ['type' => 'enabled', 'budget_tokens' => $budgetTokens]
```

Every call gets a fixed budget regardless of question complexity. "What time is my follow-up?" gets the same 8K thinking budget as "Can I take ibuprofen with propranolol given my kidney function?"

### Proposed Implementation

**Step 1: Add effort routing to `QaAssistant`**

Classify incoming questions into effort tiers before calling the API:

| Effort Level | Trigger | Example Questions |
|-------------|---------|-------------------|
| `low` | Simple factual, logistics, follow-up dates | "When is my next appointment?", "What is propranolol?" |
| `medium` | Standard clinical Q&A, single-factor | "What are common side effects of my medication?" |
| `high` | Multi-factor clinical reasoning, interactions | "Can I take ibuprofen with my beta-blocker?", "Why do I feel dizzy?" |
| `max` | Safety-critical, complex differential, multi-drug | "I have chest pain and took double dose", escalation-positive questions |

Classification can be lightweight — keyword matching on the same patterns already in `ClinicalReasoningPipeline::DEEP_TRIGGERS` (line 22-35), extended with a simple heuristic:
- Questions with drug names + interaction words → `high`
- Questions matching escalation keywords → `max`
- Questions with "what is" / "when" / "where" → `low`
- Everything else → `medium`

**Step 2: Update `AnthropicClient` to support adaptive thinking**

Add a new parameter path alongside the existing one:

```php
// New: adaptive thinking
'thinking' => ['type' => 'enabled', 'budget_tokens' => $budgetTokens]
// Keep budget_tokens as a ceiling, but add effort-based routing
```

> **IMPORTANT API CHECK**: Before implementing, verify whether Opus 4.6's PHP SDK (`anthropic-ai/laravel`) supports the adaptive thinking API parameter. The research mentions `{ type: "adaptive" }` but the current SDK uses `ThinkingConfigEnabled::with(budgetTokens:)`. If the SDK doesn't support adaptive mode natively, we use the raw curl path (already in place) and pass the parameter directly.

**Step 3: Map effort to budget_tokens (fallback if adaptive API not available)**

Even without native adaptive API, we can achieve the same effect:

| Effort | budget_tokens | max_tokens |
|--------|--------------|------------|
| `low` | 1,024 | 4,096 |
| `medium` | 4,000 | 8,000 |
| `high` | 8,000 | 16,000 |
| `max` | 16,000 | 32,000 |

**Step 4: Show effort level in UI**

Stream the effort classification to frontend as a new SSE event:
```json
{"effort": "high", "reason": "drug interaction question detected"}
```

Display as a subtle badge in the chat UI: "Deep reasoning" / "Quick answer" / "Clinical analysis".

### Files to Modify

| File | Change |
|------|--------|
| `app/Services/AI/QaAssistant.php` | Add effort classification before API call |
| `app/Services/AI/AnthropicClient.php` | Support effort-based budget selection |
| `app/Enums/AiTier.php` | Add per-effort budget mappings |
| `app/Http/Controllers/Api/ChatController.php` | Stream effort level to frontend |
| `resources/js/views/ChatView.vue` | Display effort indicator |

### Demo Value

Live demo: ask a simple question → "Quick answer" badge, 1s response. Ask a drug interaction question → "Deep reasoning" badge, visible thinking, 5s response. Judges see the system intelligently routing complexity.

### Test Strategy

- Unit test: effort classifier with 20+ question examples
- Feature test: verify different budget_tokens are passed based on question content
- No real API calls in tests

---

## Upgrade 2: Tool Use (Function Calling) for Clinical Queries

**Impact**: High | **Effort**: Medium (4-6h) | **Risk**: Medium
**Priority**: P1 — do second

### What Changes

Define native Anthropic tools that the model can call during chat, replacing some JSON prompting with structured tool use.

### Current Problem

All structured data extraction (drug interactions, lab ranges, guideline lookup) is done via prompt engineering: "Return JSON with these fields." This is fragile, not auditable, and doesn't demonstrate platform mastery.

### Proposed Tools

Define 3-4 tools that the model can call mid-conversation:

**Tool 1: `check_drug_interaction`**
```json
{
  "name": "check_drug_interaction",
  "description": "Check for interactions between two drugs using the patient's prescription list and FDA data",
  "input_schema": {
    "type": "object",
    "properties": {
      "drug1": { "type": "string", "description": "First drug name (generic or brand)" },
      "drug2": { "type": "string", "description": "Second drug name (generic or brand)" }
    },
    "required": ["drug1", "drug2"]
  }
}
```
**Backend handler**: Calls `MedsAnalyzer` + OpenFDA API, returns structured interaction data.

**Tool 2: `search_clinical_guidelines`**
```json
{
  "name": "search_clinical_guidelines",
  "description": "Search clinical guidelines (WikiDoc, DailyMed, PubMed Central) for a specific condition or treatment",
  "input_schema": {
    "type": "object",
    "properties": {
      "query": { "type": "string", "description": "Clinical topic to search" },
      "sources": {
        "type": "array",
        "items": { "type": "string", "enum": ["wikidoc", "dailymed", "pmc"] }
      }
    },
    "required": ["query"]
  }
}
```
**Backend handler**: Calls `GuidelinesRepository` with the query, returns relevant excerpts.

**Tool 3: `get_lab_reference_range`**
```json
{
  "name": "get_lab_reference_range",
  "description": "Get normal reference ranges for a lab test, adjusted for patient demographics",
  "input_schema": {
    "type": "object",
    "properties": {
      "test_name": { "type": "string" },
      "patient_age": { "type": "integer" },
      "patient_sex": { "type": "string", "enum": ["male", "female"] }
    },
    "required": ["test_name"]
  }
}
```
**Backend handler**: Returns from a hardcoded reference table (demo/lab-reference-ranges.json).

**Tool 4: `flag_for_followup`**
```json
{
  "name": "flag_for_followup",
  "description": "Flag a concern for physician follow-up with urgency level and reason",
  "input_schema": {
    "type": "object",
    "properties": {
      "urgency": { "type": "string", "enum": ["routine", "soon", "urgent"] },
      "reason": { "type": "string" },
      "suggested_timeframe": { "type": "string" }
    },
    "required": ["urgency", "reason"]
  }
}
```
**Backend handler**: Creates a `Notification` for the doctor + returns confirmation to AI.

### Implementation Approach

**Agentic loop**: When the model returns `tool_use` blocks, execute the tool server-side, send results back as `tool_result`, and continue the conversation. This requires modifying `rawCurlStream` to handle multi-turn tool use within a single SSE response.

**Key complexity**: Streaming + tool use. The model will pause streaming, emit a tool_use block, wait for the result, then resume text output. The SSE stream needs to handle this gracefully:

```
{"status": "Checking drug interactions..."}   ← tool call detected
{"tool": "check_drug_interaction", "input": {...}}  ← optional: show what AI is doing
{"text": "Based on the interaction check..."}  ← AI continues with tool result
```

### Files to Modify

| File | Change |
|------|--------|
| `app/Services/AI/AnthropicClient.php` | Add `tools` parameter support to stream/chat methods; handle tool_use blocks in SSE parser |
| `app/Services/AI/QaAssistant.php` | Define tool schemas, implement tool execution handlers |
| `app/Services/AI/ToolExecutor.php` | **NEW** — dispatches tool calls to appropriate services |
| `app/Http/Controllers/Api/ChatController.php` | Pass tool results through SSE stream |
| `resources/js/views/ChatView.vue` | Show tool usage indicators (e.g., "Checking drug database...") |
| `demo/lab-reference-ranges.json` | **NEW** — hardcoded lab reference data for demo |

### Demo Value

Judge asks: "Can I take aspirin with my propranolol?" → AI calls `check_drug_interaction` tool → UI shows "Checking drug database..." → AI responds with sourced interaction data. This is tangible, visible, and demonstrates platform mastery.

### Test Strategy

- Unit test: tool schema validation
- Feature test: mock tool execution, verify response includes tool results
- Integration test (manual): real tool call with demo data

### Risk Mitigation

- **Fallback**: If tool use fails, fall back to current JSON prompting approach
- **Timeout**: Each tool execution has a 5s timeout
- **Circuit breaker**: If tool calls add >10s latency, disable for the session

---

## Upgrade 3: Deep Context Loading — "Full Patient Brain"

**Impact**: High (demo wow factor) | **Effort**: Low-Medium (2-4h) | **Risk**: Low
**Priority**: P1 — can parallelize with Upgrade 2

### What Changes

Intentionally load maximum context to demonstrate the 1M window advantage. Currently loading ~80-100K tokens; push to 250-400K with richer data.

### Current Context Assembly

```
System prompt:        ~1,150 tokens
Guidelines:           ~65,000 tokens (with PMC article)
Visit data:           ~5,000 tokens
Patient record:       ~1,500 tokens
Health history:       ~1,000 tokens
Recent visits:        ~500 tokens
Device data:          ~1,500 tokens
Medications:          ~750 tokens
FDA safety:           ~750 tokens
Library items:        ~2,500 tokens
Conversation:         ~500 tokens
─────────────────────────────────
TOTAL:                ~80,000 tokens
```

### Proposed Deep Context

Add these layers to `ContextAssembler` (only on Opus46 tier):

| New Layer | Source | Estimated Tokens | Implementation |
|-----------|--------|-----------------|----------------|
| **Multi-visit history** | All past visits (not just 3), full SOAP notes | +20,000-40,000 | Query all visits with visit notes, not just recent 3 |
| **Full drug labels** (FDA) | Complete DailyMed SPL labels, not 500-char truncations | +10,000-20,000 | Remove `substr($label, 0, 500)` cap |
| **Multiple PMC articles** | Load 2-3 relevant articles instead of 1 | +60,000-120,000 | Expand `GuidelinesRepository` to return multiple matches |
| **Patient education materials** | Pre-written condition summaries from guidelines | +5,000-10,000 | Bundle in `demo/education/` |
| **Full Apple Watch data** | Expanded time range (3 months vs current) | +5,000-10,000 | Load more data points |

**New total: ~180,000-280,000 tokens** — demonstrably deep, with room in 1M window.

### Token Counter Display

Add a token counter to the doctor dashboard that shows real-time context load:

```
Context loaded: 247,382 tokens
├── Clinical guidelines: 142,000 (3 PMC articles)
├── Patient record: 45,000 (8 visits, full SOAP)
├── Medications + FDA: 28,000
├── Wearable data: 15,000 (3 months)
├── Personal library: 12,000
└── System + conversation: 5,382
```

This is a **demo artifact** — it makes the invisible (context loading) visible and impressive.

### Files to Modify

| File | Change |
|------|--------|
| `app/Services/AI/ContextAssembler.php` | Expand layers, remove truncation caps on Opus46 tier |
| `app/Services/AI/GuidelinesRepository.php` | Support loading multiple PMC articles |
| `app/Http/Controllers/Api/ChatController.php` | Return token count metadata after context assembly |
| `resources/js/views/DoctorVisitView.vue` | Display token counter on doctor dashboard |
| `docs/opus-4.6-usage.md` | Update context window documentation |

### Demo Value

Video overlay: "Context loaded: 247,382 tokens — full medical record in a single prompt." No other model can do this. This is the single most visually impressive claim.

### Test Strategy

- Feature test: verify context assembly includes all layers
- Unit test: token count estimation accuracy
- No real API calls needed — just context assembly

---

## Upgrade 4: 128K Output — Comprehensive Patient Education

**Impact**: Medium | **Effort**: Low (1-2h) | **Risk**: Low
**Priority**: P2 — nice to have

### What Changes

Add a "Tell me everything" mode that generates a comprehensive, multi-section patient education document using Opus's 128K output capacity.

### Implementation

New endpoint: `POST /api/v1/visits/{visit}/education`

When triggered, generates a comprehensive document:
1. **Your Visit Summary** (plain language)
2. **Your Conditions Explained** (each condition, causes, prognosis)
3. **Your Medications Guide** (each drug: what it does, how to take, side effects, interactions)
4. **Diet & Lifestyle Recommendations** (specific to conditions)
5. **Warning Signs to Watch For** (red flags requiring immediate care)
6. **Questions for Your Next Visit** (suggested follow-up questions)
7. **Glossary** (all medical terms explained)

**API parameters:**
```json
{
  "max_tokens": 65536,
  "thinking": { "type": "enabled", "budget_tokens": 16000 }
}
```

This uses 50% of the 128K output capacity — enough to generate a 15-20 page document.

### Files to Modify

| File | Change |
|------|--------|
| `app/Services/AI/PatientEducationGenerator.php` | **NEW** — orchestrates comprehensive document generation |
| `prompts/patient-education.md` | **NEW** — system prompt for education documents |
| `app/Http/Controllers/Api/EducationController.php` | **NEW** — endpoint |
| `routes/api.php` | Add route |
| `resources/js/views/VisitView.vue` | Add "Generate full guide" button |

### Demo Value

Patient clicks "Tell me everything about my visit" → AI generates a 15-page personalized medical guide in real-time. Streamed to UI with progress. Shows 128K output in action.

### Test Strategy

- Feature test: endpoint returns 200, mock AI response
- Manual: verify output quality with demo scenario

---

## Upgrade 5: Context Compaction for Longitudinal Care

**Impact**: Medium-High | **Effort**: High (6-8h) | **Risk**: Medium
**Priority**: P3 — defer if time-constrained

### What Changes

Enable cross-session memory so returning patients get continuity. Opus 4.6's context compaction auto-compresses older conversation turns while preserving key medical context.

### Why This Matters

Currently, each chat session starts from scratch. If a patient asks about drug interactions on Monday, then returns Wednesday with "remember the side effect I mentioned?", the system has no memory.

### Proposed Implementation

**Step 1: Persist conversation context**

Store compacted conversation summaries in a new `patient_context_summaries` table:

```
id, patient_id, visit_id, summary_text, token_count, created_at, updated_at
```

After each chat session (5+ messages), generate a summary:
- Key medical questions asked
- Important concerns raised
- Follow-up items mentioned
- Emotional state / anxiety indicators

**Step 2: Load historical context on new sessions**

When assembling context for a new chat session, load the last 3-5 context summaries (~5K tokens total) and prepend them:

```
"Previous interactions with this patient:
- Feb 12: Asked about propranolol side effects. Concerned about fatigue.
- Feb 13: Reported dizziness after standing. Advised to stand slowly.
- Feb 14: Asked about drug interaction with ibuprofen. Advised to avoid."
```

**Step 3: Enable API-level context compaction**

If the Anthropic API supports a `context_compaction` parameter (needs verification), enable it for long conversations to automatically compress earlier turns while preserving medical context.

### Files to Modify

| File | Change |
|------|--------|
| `database/migrations/` | New migration for `patient_context_summaries` |
| `app/Models/PatientContextSummary.php` | **NEW** — model |
| `app/Services/AI/ContextAssembler.php` | Load historical summaries |
| `app/Services/AI/SessionSummarizer.php` | **NEW** — generates session summaries |
| `app/Http/Controllers/Api/ChatController.php` | Trigger summarization after session ends |

### Demo Value

Show patient returning after "2 days" (simulated): "Last time you mentioned feeling dizzy when standing up. How has that been?" — AI remembers without being told. This demonstrates longitudinal care continuity.

### Test Strategy

- Feature test: summary generation and loading
- Feature test: verify historical context appears in assembled context
- Manual: multi-session demo walkthrough

---

## Implementation Order & Timeline

Given hackathon deadline (Feb 16, 15:00 EST), here's the recommended execution order:

| # | Upgrade | Est. Hours | Parallelizable | Deadline Risk |
|---|---------|-----------|----------------|---------------|
| 1 | **Adaptive Thinking** | 2-3h | No (touches core client) | Low |
| 2 | **Deep Context + Token Counter** | 2-4h | Yes (after #1) | Low |
| 3 | **Tool Use** | 4-6h | Yes (after #1) | Medium |
| 4 | **128K Output Education** | 1-2h | Yes (independent) | Low |
| 5 | **Context Compaction** | 6-8h | Yes (independent) | **High — skip if <12h remain** |

### Minimum Viable Demo Enhancement (if only 6h available)

Do upgrades **1 + 2 + 4** = adaptive thinking + deep context with token counter + education generator. This covers:
- Adaptive thinking (most technically impressive)
- Token counter (most visually impressive for video)
- Education doc (128K output demo)

Total: ~5-8h, low risk, high demo impact.

### Full Enhancement (if 12h+ available)

Do all 5 in order. Tool use (#3) is the highest risk/reward addition.

---

## Impact on Existing Tests

All upgrades are **additive and backwards-compatible**:

- Upgrade 1: New effort classification is a pre-processing step. Existing tests pass with default `medium` effort.
- Upgrade 2: Tool use is optional — disabled when `tools` parameter is empty. Existing non-tool paths unchanged.
- Upgrade 3: Context expansion only on Opus46 tier. Lower tiers unchanged.
- Upgrade 4: New endpoint, no existing code modified.
- Upgrade 5: New table + services, no existing models modified.

**Test additions needed:**
- ~15-20 new unit/feature tests across all upgrades
- Zero changes to existing tests

---

## Documentation Updates Required

After implementation:

| File | Updates |
|------|---------|
| `docs/opus-4.6-usage.md` | Full rewrite — add adaptive thinking, tool use, deep context sections |
| `docs/api.md` | New endpoint for education generator |
| `docs/architecture.md` | Update AI pipeline diagram with tool use flow |
| `docs/ai-prompts.md` | New prompts (patient-education.md) |
| `CHANGELOG.md` | All new features |
| `README.md` | Update feature list, architecture diagram |

---

## Open Questions

1. **Adaptive thinking API availability**: Does `anthropic-ai/laravel` SDK support `{ type: "adaptive" }` or only `{ type: "enabled", budget_tokens: N }`? If not, can we pass it through raw curl?

2. **Tool use + streaming**: Does the raw curl SSE parser handle `content_block_start` with `type: "tool_use"` correctly? Needs investigation of the SSE event format for tool calls.

3. **PMC article loading latency**: Loading 3 PMC articles could add 5-10s to first request. Should we pre-fetch on visit load?

4. **128K output cost**: A single 65K output request costs ~$4.88 at $75/1M. Acceptable for demo but needs a guard in production.

5. **Context compaction API**: Is this a model-level feature (automatic) or does it require an API parameter? The research mentions it but API docs need verification.

---

*This PRD is a living document. Update as implementation progresses.*
