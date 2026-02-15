# Why Opus 4.6: Context Compaction for Longitudinal Care

**Status**: Architecture ready — implementation deferred post-hackathon
**Priority**: P3 — high value for product vision, high effort (6-8h)

---

## The Feature

PostVisit.ai remembers patient interactions across sessions, enabling longitudinal care continuity. When a patient returns days or weeks later, the AI recalls prior concerns, questions, and health patterns.

## Why Opus 4.6 Specifically

Opus 4.6's **1M context window** combined with its deep reasoning makes cross-session memory practical:

1. **Massive context capacity**: Previous session summaries (5-10K tokens each) barely dent the 1M window
2. **Extended thinking**: The model can reason about historical patterns while maintaining current context
3. **Nuanced comprehension**: Opus 4.6 can identify clinically relevant patterns across compressed session summaries that simpler models would miss

## How It Works

### Session Summary Generation

After each chat session (5+ messages), the AI generates a structured summary:

```json
{
  "patient_id": "uuid",
  "visit_id": "uuid",
  "session_date": "2026-02-13",
  "key_questions": [
    "Asked about propranolol side effects",
    "Concerned about fatigue and exercise tolerance"
  ],
  "concerns_raised": [
    "Dizziness when standing up quickly",
    "Worried about long-term medication dependence"
  ],
  "followup_items": [
    "Monitor blood pressure at home",
    "Try taking medication before bed instead of morning"
  ],
  "emotional_context": "Anxious about diagnosis, responsive to reassurance",
  "token_count": 450
}
```

### Historical Context Loading

When assembling context for a new session, the last 3-5 session summaries are loaded:

```
Previous interactions with this patient:

Session Feb 12: Asked about propranolol side effects. Concerned about
fatigue. Advised to take medication before bed. Emotional state: anxious
but receptive.

Session Feb 13: Reported dizziness after standing. Advised to stand
slowly and stay hydrated. Asked about drug interaction with ibuprofen —
advised to avoid NSAIDs with beta-blockers.

Session Feb 14: Followed up on dizziness — improved after changing
medication timing. New question about alcohol interaction.
```

### Database Schema

```sql
CREATE TABLE patient_context_summaries (
    id UUID PRIMARY KEY,
    patient_id UUID NOT NULL REFERENCES patients(id),
    visit_id UUID REFERENCES visits(id),
    summary_text TEXT NOT NULL,
    key_questions JSONB DEFAULT '[]',
    concerns_raised JSONB DEFAULT '[]',
    followup_items JSONB DEFAULT '[]',
    emotional_context TEXT,
    token_count INTEGER DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

## Architecture

### Files to Create/Modify

| File | Change |
|------|--------|
| `database/migrations/` | New migration for `patient_context_summaries` |
| `app/Models/PatientContextSummary.php` | **NEW** — Eloquent model |
| `app/Services/AI/SessionSummarizer.php` | **NEW** — generates session summaries via Opus |
| `app/Services/AI/ContextAssembler.php` | Load historical summaries as additional context layer |
| `app/Http/Controllers/Api/ChatController.php` | Trigger summarization after session ends (async job) |
| `app/Jobs/SummarizeChatSession.php` | **NEW** — queued job for async summarization |

### Flow

1. Patient chats with AI (existing flow)
2. After session ends (5+ messages, tab close or timeout)
3. `SummarizeChatSession` job queued
4. Job calls `SessionSummarizer` → Opus generates structured summary
5. Summary stored in `patient_context_summaries`
6. Next session: `ContextAssembler` loads last 3-5 summaries (~2-5K tokens)
7. AI has full historical context without loading raw conversation history

## Demo Value

**Simulated scenario**: Show a patient returning after "2 days":
- AI greets with: "Last time you mentioned feeling dizzy when standing up. How has that been?"
- Patient didn't re-explain the symptom — AI remembered from context compaction
- Demonstrates longitudinal care continuity, a key clinical value proposition

## Why Not Implemented Yet

1. **Database migration risk**: Adding tables close to deadline risks seeder/test issues
2. **Async job complexity**: Session-end detection and reliable job dispatch need careful handling
3. **Summary quality**: AI-generated summaries need prompt tuning for clinical relevance
4. **Testing burden**: Cross-session tests are inherently integration-heavy

**Implementation estimate**: 6-8 hours with testing.

## Current Alternative

Currently each session starts fresh but loads the full patient record, visit history, and health data. The AI can reason about medical history but not about prior conversations.

## Future Vision

Context compaction is the foundation for PostVisit.ai's longitudinal care model:
- **Trend detection**: "You've asked about fatigue in 3 out of 4 sessions — let's discuss this with your doctor"
- **Adherence tracking**: "You mentioned missing doses twice last week"
- **Emotional patterns**: "You seem less anxious about your diagnosis this week"
- **Proactive recommendations**: "Based on our conversations, here are topics for your next appointment"
