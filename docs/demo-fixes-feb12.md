# Demo Fixes — Feb 12 Working Document

**Branch:** agent2-workspace
**Source:** Manual demo walkthrough + code audit (Feb 12)
**Goal:** Fix all MUST issues, prepare for submission

---

## Status Legend
- BUG = broken in production, needs immediate fix
- TODO = not yet implemented, required for demo
- VERIFIED DONE = confirmed working in code audit

---

## Issues to Fix

### 1. BUG: Fatima Benali scenario — "Error loading scenario"
- **Screen:** Scenario Picker → scenario `fibromyalgia` (visit-05)
- **Symptom:** Frontend shows "Error loading scenario" when clicking Fatima Benali card
- **Data exists:** `demo/visits/visit-05-arm-pain-fibromyalgia/` has all files (patient-profile.json, dialogue.txt, etc.)
- **Config exists:** `config/demo-scenarios.php` has `fibromyalgia` entry (lines 369-392)
- **Likely cause:** Missing required field in config, or seeder fails on specific data (need to check API response)
- **Priority:** HIGH — fix first

### 2. TODO: Expand scenarios to 12 total
- **Current:** 9 active scenarios (all cardiology, all under Dr. Nedo)
- **Target:** 12 scenarios total
- **Requirements:**
  - Add 3 new scenarios covering: endocrinology (diabetes), gastroenterology, + 1 more
  - Add demographic diversity: Latino patient, Asian patient
  - All patient data (name, age, demographics, conditions, meds) must be **hardcoded in config**, NOT dynamically generated
  - Once generated, data stays frozen in config — user modifies manually later
- **New scenarios needed:**
  - Endocrinology / Type 2 Diabetes (Latino patient)
  - Gastroenterology / IBS or IBD (Asian patient)
  - 1 more TBD (could be: pulmonology, neurology, orthopedics)

### 3. TODO: Scenario Picker UI — 4 highlighted + "Show more"
- **Current:** All 9 scenarios shown in flat grid
- **Target:**
  - 4 highlighted/featured patients at top (cards larger or visually prominent)
  - "Show more" expander for remaining 8
  - Specialty filter/selector
  - Specialties without scenarios in DB shown as **greyed out** (disabled) in filter
- **Depends on:** #2 (need 12 scenarios first)

### 4. PARTIAL: Term Highlighter in AI Analysis (attachments)
- **Status:** HighlightedText.vue works for SOAP sections
- **Missing:** Term extraction not running on document analysis results
- **Fix:** Run TermExtractor on AI analysis output in DocumentController, or use frontend text-search fallback
- **Priority:** MEDIUM

---

## Verified DONE (no action needed)

| Item | Status | Notes |
|------|--------|-------|
| Scenario Picker exists | DONE | `/demo/scenarios` route + ScenarioPicker.vue |
| "Try Demo" → /login | DONE | Not auto-login anymore |
| Login Demo Access glow | DONE | Emerald glow animation |
| Visit Detail short summary | DONE | Gradient card at top |
| Two-column layout | DONE | Visit left + Chat right |
| "Ask about this" buttons | DONE | On all SOAP sections |
| Chat slide-in panel | DONE | Reusable ChatPanel.vue |
| Doctor Dashboard alerts | DONE | Weight/BP trend alerts |
| Patient search backend | DONE | DoctorController filters |
| Quality gate | DONE | TranscriptController validates |
| "Use Demo Recording" button | DONE | In CompanionScribe.vue |
| AI Analysis auto-refresh | DONE | Polling in VisitAttachments.vue |
| PII "Dr. Ciarka" removed | DONE | Not in codebase |
| Chat streaming (SSE) | DONE | Working |
| Medical term highlighting | DONE | In SOAP sections |
| Landing page | DONE | Clean, two entry points |

---

## Execution Plan

1. **Fix Fatima Benali bug** — investigate API error, fix config/seeder
2. **Generate 3 new scenarios** — hardcoded clinical data in config
3. **Update Scenario Picker UI** — 4 featured + show more + specialty filter
4. **Term highlighter in AI Analysis** — if time permits
