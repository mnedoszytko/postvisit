# Opus 4.6 Usage in PostVisit.ai

This document explains how PostVisit.ai leverages Claude Opus 4.6's unique capabilities to deliver clinical-grade AI assistance. It covers the safety classification rationale, extended thinking for clinical reasoning, context window utilization, prompt caching economics, the 3-tier comparison architecture, and per-subsystem thinking budgets.

## Table of Contents

1. [ASL-4 Safety Classification Rationale](#asl-4-safety-classification-rationale)
2. [Extended Thinking for Clinical Reasoning](#extended-thinking-for-clinical-reasoning)
3. [1M Context Window Usage](#1m-context-window-usage)
4. [Prompt Caching Economics](#prompt-caching-economics)
5. [3-Tier Comparison Architecture](#3-tier-comparison-architecture)
6. [Per-Subsystem Thinking Budgets](#per-subsystem-thinking-budgets)

---

## ASL-4 Safety Classification Rationale

PostVisit.ai operates in a healthcare-adjacent domain where safety is paramount. We chose Opus 4.6 specifically because its safety characteristics align with the requirements of a patient-facing clinical assistant.

### Why ASL-4 Matters for Healthcare AI

Opus 4.6 is Anthropic's most capable and safety-aligned model. In the context of a post-visit patient assistant, safety manifests in several critical dimensions:

1. **Refusal to diagnose or prescribe**: The model must reliably refuse to provide new diagnoses or prescribe medications, even when patients phrase questions in ways that implicitly request medical advice. Opus 4.6's instruction-following fidelity is critical here.

2. **Escalation detection**: When a patient describes symptoms that could indicate a medical emergency (chest pain, difficulty breathing, suicidal ideation), the system must detect this reliably. We use Opus with extended thinking for escalation decisions (`EscalationDetector`), allowing the model to reason through clinical context before making urgency classifications.

3. **Hallucination resistance**: In healthcare, a hallucinated drug interaction or fabricated guideline could be dangerous. Opus 4.6's grounding in provided context (visit data, guidelines) with explicit source attribution requirements reduces confabulation risk.

4. **Nuanced clinical reasoning**: Clinical questions often have complex, context-dependent answers. "Can I take ibuprofen with my beta-blocker?" requires understanding pharmacology, the patient's specific conditions, and their prescription history. Opus 4.6's reasoning depth handles these multi-factor clinical questions.

### Safety Guardrails in Code

The safety architecture is implemented across multiple layers:

- **`EscalationDetector`** (`app/Services/AI/EscalationDetector.php`): Two-tier urgency detection. Fast keyword matching for critical symptoms, then Opus with extended thinking for nuanced clinical reasoning.
- **`ClinicalReasoningPipeline`** (`app/Services/AI/ClinicalReasoningPipeline.php`): Plan-Execute-Verify pattern where Phase 3 validates the AI response against clinical guidelines before delivery.
- **`qa-assistant.md`** prompt: 85-line behavioral specification with explicit rules for what the AI can and cannot do, including escalation protocol and source attribution requirements.

---

## Extended Thinking for Clinical Reasoning

Extended thinking is the single most important Opus 4.6 feature for PostVisit.ai. It allows the model to "think through" clinical reasoning before generating a patient-facing response.

### How Extended Thinking Is Used

1. **Patient Q&A (`QaAssistant`)**: Every patient question is answered with extended thinking enabled. The model reasons through the visit context, clinical guidelines, and medication data before composing a response. Thinking budget: **8,000 tokens**.

2. **Escalation Detection (`EscalationDetector`)**: On the Opus 4.6 tier, escalation decisions use extended thinking to consider the patient's conditions, medications, and symptom context before classifying urgency. This prevents false positives (e.g., a patient describing post-exercise shortness of breath that's expected with their condition). Thinking budget: **6,000 tokens**.

3. **Reverse Scribe Processing (`ScribeProcessor`)**: When converting a patient-recorded transcript into a structured SOAP note, extended thinking allows the model to reason about clinical significance, differential diagnoses mentioned by the doctor, and the appropriate level of detail for each SOAP section. Thinking budget: **10,000 tokens**.

4. **Clinical Reasoning Pipeline (`ClinicalReasoningPipeline`)**: The Plan and Verify phases both use extended thinking. Plan phase reasons about which knowledge domains are relevant; Verify phase reasons about clinical accuracy and safety compliance. Thinking budget: **10,000 tokens** (reasoning subsystem).

### Thinking Visibility

Extended thinking is streamed to the frontend via SSE. The UI shows a "thinking" indicator while the model reasons, giving patients transparency into the AI's deliberation process. Thinking content is also stored in chat message metadata for audit purposes.

---

## 1M Context Window Usage

Opus 4.6's 1,000,000-token context window is a core architectural enabler. It allows us to load comprehensive clinical context that would be impossible with smaller context windows.

### Context Assembly Layers

The `ContextAssembler` (`app/Services/AI/ContextAssembler.php`) builds context in 5 layers:

| Layer | Content | Typical Size |
|-------|---------|-------------|
| System Prompt | Behavioral rules, escalation protocol, response format | ~2,000 tokens |
| Clinical Guidelines | WikiDoc articles + DailyMed labels + PMC Open Access articles | ~50,000-150,000 tokens |
| Visit Data | SOAP note, transcript, observations, conditions | ~5,000-20,000 tokens |
| Patient Record | Demographics, conditions, prescriptions, history | ~1,000-3,000 tokens |
| FDA Safety Data | Adverse events, drug labels, boxed warnings | ~2,000-5,000 tokens |

**Total typical context: ~60,000-180,000 tokens** per request, with room for conversation history.

### PMC Open Access Articles

The `GuidelinesRepository` loads full-text articles from PubMed Central via the BioC API:

- **PVC Management Consensus (PMC7880852)**: ~30,000-50,000 tokens
- **2022 AHA/ACC/HFSA Heart Failure Guidelines (PMC9386162)**: ~80,000-100,000 tokens
- **2017 ACC/AHA Hypertension Guidelines (PMC7384247)**: ~60,000-80,000 tokens

These articles are fetched at runtime (not bundled in the repo) and cached for 24 hours. The `MAX_WORDS` limit is set to 50,000 words (~66,000 tokens) per article to demonstrate large context utilization.

### Why Large Context Matters

With smaller context windows (e.g., 128K or 200K), we would need to:
- Truncate guidelines to summaries, losing clinical nuance
- Use RAG with retrieval, adding latency and missing relevant passages
- Choose between transcript or guidelines, not both

With 1M tokens, we load everything: full transcript + full guidelines + full medication data + conversation history. The model has the complete picture, just as a physician would when reviewing a chart.

---

## Prompt Caching Economics

Prompt caching reduces the cost of repeated requests by caching stable content blocks (system prompt + guidelines) server-side.

### Implementation

The `ContextAssembler` creates cacheable `TextBlockParam` blocks with `CacheControlEphemeral` annotations:

```
Block 1: System prompt (qa-assistant.md) — cached
Block 2: Clinical guidelines (WikiDoc + DailyMed + PMC) — cached
---
Messages: Visit data, patient record, FDA data, conversation — not cached
```

### Cost Savings

Anthropic prompt caching pricing (as of Feb 2026):

| Token Type | Price per 1M tokens | PostVisit Usage |
|-----------|-------------------|-----------------|
| Input (no cache) | $15.00 | First request only |
| Cache write | $18.75 | First request per 5-min window |
| Cache read | $1.50 | All subsequent requests |
| Output | $75.00 | All requests |

For a typical conversation (10 messages, ~150K cached tokens per request):

- **Without caching**: 10 x 150K x $15/1M = **$22.50** input cost
- **With caching**: 1 x 150K x $18.75/1M + 9 x 150K x $1.50/1M = $2.81 + $2.03 = **$4.84** input cost
- **Savings**: ~78% reduction in input token costs

The 5-minute cache TTL (configurable via `ANTHROPIC_CACHE_TTL`) covers typical patient chat sessions (5-15 minutes of active Q&A).

### Cache Strategy

- **System prompt**: Stable across all requests for the same prompt type. Cache hit rate: ~95%.
- **Clinical guidelines**: Stable for the same visit (same conditions = same guidelines). Cache hit rate: ~90%.
- **Visit data**: Per-request (visit-specific data changes per conversation). Not cached.

---

## 3-Tier Comparison Architecture

PostVisit.ai implements a 3-tier AI architecture to demonstrate the progressive value of Opus 4.6 over simpler configurations. This is controlled by the `AiTier` enum (`app/Enums/AiTier.php`) and managed via the `AiTierManager`.

### Tier Comparison Table

| Feature | Good (Standard) | Better (Enhanced) | Opus 4.6 (Clinical Intelligence) |
|---------|----------------|-------------------|----------------------------------|
| **Model** | Sonnet 4.5 | Opus 4.6 | Opus 4.6 |
| **Extended Thinking** | No | Chat + Scribe | All subsystems |
| **Thinking Budget (Chat)** | 0 | 4,000 tokens | 8,000 tokens |
| **Thinking Budget (Scribe)** | 0 | 6,000 tokens | 10,000 tokens |
| **Thinking Budget (Escalation)** | 0 | 0 | 6,000 tokens |
| **Thinking Budget (Reasoning)** | 0 | 6,000 tokens | 10,000 tokens |
| **Escalation Detection** | Keywords only | Keywords + Sonnet AI | Keywords + Opus with thinking |
| **Clinical Guidelines** | None | None | WikiDoc + DailyMed + PMC |
| **Prompt Caching** | No | Yes | Yes |
| **Clinical Reasoning Pipeline** | No | No | Plan-Execute-Verify |
| **Approx. Cost per Chat** | ~$0.02 | ~$0.15 | ~$0.30-0.50 |

### How Tiers Are Switched

The active tier is stored in cache and switchable via API:

```
PUT /api/v1/settings/ai-tier
{ "tier": "opus46" }
```

The doctor dashboard includes a tier selector. During the demo, switching tiers in real-time shows the progressive improvement in response quality, reasoning depth, and safety detection.

### Demo Strategy

1. Start with **Good** tier: Show basic Q&A with simple responses, no thinking visible
2. Switch to **Better** tier: Show extended thinking, deeper responses, but no guidelines
3. Switch to **Opus 4.6** tier: Show full clinical reasoning pipeline, guidelines-backed answers, thinking on escalation decisions

This progression makes the value of each Opus 4.6 feature tangible and comparable.

---

## Per-Subsystem Thinking Budgets

Each AI subsystem has a calibrated thinking budget that balances reasoning depth with latency and cost.

### Budget Allocation (Opus 4.6 Tier)

| Subsystem | Budget (tokens) | Rationale |
|-----------|----------------|-----------|
| **Chat** (`QaAssistant`) | 8,000 | Patient questions need thorough reasoning but fast responses. 8K allows multi-step clinical reasoning without excessive wait times. |
| **Scribe** (`ScribeProcessor`) | 10,000 | Transcript-to-SOAP conversion is the most complex task. Must reason about clinical significance, differential diagnoses, and SOAP section assignment. Highest budget. |
| **Escalation** (`EscalationDetector`) | 6,000 | Safety-critical but needs to be fast. 6K allows context-aware reasoning (patient's conditions, medications) without delay. |
| **Reasoning** (`ClinicalReasoningPipeline`) | 10,000 | Plan and Verify phases of the pipeline. Complex clinical reasoning requires deep thinking. Matches Scribe budget. |

### Why Per-Subsystem Budgets Matter

A flat thinking budget (e.g., 10,000 for everything) would be:
- **Too slow** for escalation detection (where speed saves lives)
- **Too shallow** for scribe processing (where accuracy prevents medical errors)
- **Too expensive** for simple chat messages that don't need deep reasoning

Per-subsystem budgets let us optimize the latency/quality/cost tradeoff for each use case.

### Budget Progression Across Tiers

```
                    Good    Better    Opus 4.6
Chat:                 0      4,000      8,000
Scribe:               0      6,000     10,000
Escalation:           0          0      6,000
Reasoning:            0      6,000     10,000
                   ----     ------    -------
Total capacity:       0     16,000     34,000 tokens of thinking
```

The Opus 4.6 tier uses 2.125x more thinking capacity than the Better tier, reflecting the model's deeper reasoning capabilities and the higher clinical accuracy requirements at the top tier.

---

*Last updated: 2026-02-11*
