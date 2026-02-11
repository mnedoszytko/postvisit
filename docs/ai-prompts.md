# AI Prompts Documentation

This document catalogs all AI system prompts used in PostVisit.ai. Prompts are stored as versioned files in the `prompts/` directory and loaded via the `PromptLoader` service.

---

## term-extractor

**File:** `prompts/term-extractor.md`
**Service:** `App\Services\AI\TermExtractor`
**Model:** Opus 4.6 (production), Sonnet (tests)

### Purpose

Extracts clinically relevant medical terms from SOAP note sections and returns their exact character positions. Used to power the tap-to-explain highlighting feature (PRD user story P3).

### Input Format

The service concatenates non-empty SOAP sections from a `VisitNote` model into a single user message:

```
=== SECTION: chief_complaint ===
Patient reports heart palpitations occurring 3-4 times per week...

=== SECTION: history_of_present_illness ===
52-year-old male presenting with a 3-month history of intermittent heart palpitations...

=== SECTION: assessment ===
Premature ventricular contractions (PVCs) — benign pattern...

=== SECTION: plan ===
1. Start propranolol 40 mg twice daily...
```

Sections processed: `chief_complaint`, `history_of_present_illness`, `review_of_systems`, `physical_exam`, `assessment`, `plan`.

### Expected Output

Pure JSON (no markdown fences) mapping section names to arrays of term objects:

```json
{
  "chief_complaint": [
    { "term": "palpitations", "start": 6, "end": 18 }
  ],
  "assessment": [
    { "term": "Premature ventricular contractions", "start": 0, "end": 35 },
    { "term": "PVCs", "start": 36, "end": 40 }
  ],
  "plan": [
    { "term": "propranolol", "start": 9, "end": 20 }
  ]
}
```

Each entry:
- `term` (string) -- exact text as it appears in the section
- `start` (int) -- 0-based character index of the first character
- `end` (int) -- 0-based index one past the last character (end = start + length)

### Extraction Rules

1. **Include:** diagnoses, symptoms, procedures, medications, lab values, anatomical terms, medical abbreviations
2. **Exclude:** common clinical words ("patient", "history", "normal"), section headers, numbers without clinical context
3. **Specificity:** prefer longer phrases over individual words (e.g., "premature ventricular contractions" over "contractions")
4. **No overlaps:** if a longer phrase contains a shorter term, only the longer phrase is extracted
5. **Abbreviations:** include as separate terms only when they appear independently, not when inside parentheses following the full term
6. **Ordering:** terms listed in order of appearance (ascending start position)

### Validation

The `TermExtractor` service validates every returned offset server-side:
1. Checks `start >= 0`, `end <= strlen(text)`, and `end > start`
2. Extracts `substr(text, start, end - start)` and compares case-insensitively against the claimed term
3. Invalid entries are dropped and logged to the `ai` channel
4. The frontend (`HighlightedText.vue`) performs a second validation pass and falls back to string search if offsets don't match

### Version History

| Date | Change | Reason |
|------|--------|--------|
| 2026-02-11 | v1 — Initial prompt | Medical term highlighting feature |

### Max Tokens

4096 -- sufficient for ~40 terms across 6 SOAP sections.
