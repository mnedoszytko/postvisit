# Why Opus 4.6: Native Tool Use (Function Calling)

**Status**: Architecture ready — implementation deferred post-hackathon
**Priority**: P1 — high impact but medium risk, requires agentic loop in SSE streaming

---

## The Feature

PostVisit.ai uses Opus 4.6's native function calling to let the AI query real medical databases mid-conversation — drug interaction checks, clinical guideline searches, lab reference ranges, and follow-up flagging.

## Why Opus 4.6 Specifically

Opus 4.6 supports **native tool use** (function calling) where the model can:
1. Decide which tools to call based on the question context
2. Generate structured input parameters
3. Receive tool results and incorporate them into the response
4. Chain multiple tool calls when needed

This is fundamentally different from JSON prompting: the model **actively requests** information it needs, rather than being force-fed everything upfront.

## Proposed Tools

### Tool 1: `check_drug_interaction`
**Purpose**: Check interactions between two drugs using the patient's prescription list and FDA data.

```json
{
  "name": "check_drug_interaction",
  "description": "Check for interactions between two drugs",
  "input_schema": {
    "type": "object",
    "properties": {
      "drug1": { "type": "string", "description": "First drug name" },
      "drug2": { "type": "string", "description": "Second drug name" }
    },
    "required": ["drug1", "drug2"]
  }
}
```

**Backend handler**: Calls existing `MedsAnalyzer` + OpenFDA API, returns structured interaction data.

### Tool 2: `search_clinical_guidelines`
**Purpose**: Search clinical guidelines (WikiDoc, DailyMed, PubMed Central) for a specific condition or treatment.

```json
{
  "name": "search_clinical_guidelines",
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

**Backend handler**: Calls existing `GuidelinesRepository` with the query, returns relevant excerpts.

### Tool 3: `get_lab_reference_range`
**Purpose**: Get normal reference ranges for a lab test, adjusted for patient demographics.

**Backend handler**: Returns from a hardcoded reference table (`demo/lab-reference-ranges.json`).

### Tool 4: `flag_for_followup`
**Purpose**: Flag a concern for physician follow-up with urgency level.

**Backend handler**: Creates a `Notification` for the doctor + returns confirmation to AI.

## Architecture

### Agentic Loop in SSE Streaming

The key challenge is handling tool calls within the existing SSE streaming pipeline:

```
Patient asks question
  → AI starts streaming response
  → AI emits tool_use block (e.g., check_drug_interaction)
  → Server executes tool
  → Server sends tool_result back to AI
  → AI continues streaming with tool results incorporated
  → SSE events show: "Checking drug interactions..." → result text
```

### Files to Modify

| File | Change |
|------|--------|
| `app/Services/AI/AnthropicClient.php` | Add `tools` parameter to stream methods; handle `tool_use` content blocks in SSE parser |
| `app/Services/AI/QaAssistant.php` | Define tool schemas, implement tool execution dispatch |
| `app/Services/AI/ToolExecutor.php` | **NEW** — dispatches tool calls to appropriate services |
| `app/Http/Controllers/Api/ChatController.php` | Pass tool results through SSE stream |
| `resources/js/components/ChatPanel.vue` | Show tool usage indicators ("Checking drug database...") |
| `demo/lab-reference-ranges.json` | **NEW** — hardcoded lab reference data |

### Risk Mitigation

- **Fallback**: If tool use fails, fall back to current JSON prompting approach
- **Timeout**: Each tool execution has a 5s timeout
- **Circuit breaker**: If tool calls add >10s latency, disable for the session

## Demo Value

Judge asks: "Can I take aspirin with my propranolol?" → AI calls `check_drug_interaction` → UI shows "Checking drug database..." → AI responds with sourced interaction data.

This makes the AI's reasoning process visible and demonstrates platform mastery.

## Why Not Implemented Yet

Tool use + streaming requires an agentic loop (multi-turn within a single SSE connection) which is architecturally complex in the current `rawCurlStream` implementation. The risk of breaking the existing demo-critical chat flow is too high for the hackathon deadline.

**Implementation estimate**: 4-6 hours with testing.

## Current Alternative

The same information is already provided via context assembly (FDA data, guidelines loaded upfront). Tool use would make this on-demand rather than pre-loaded, reducing initial context size but adding latency.
