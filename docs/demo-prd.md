# Demo PRD — PostVisit.ai Hackathon Submission

**Deadline:** 16 lutego 2026, 15:00 EST
**Created:** 2026-02-11
**Source:** Full E2E demo walkthrough documented in `docs/demo-checklist.md`
**Original PRD:** `docs/prd.md` (historical — foundational requirements, not actively maintained)

---

## 1. Product Vision (Demo Narrative)

PostVisit.ai keeps the clinical context of a specific visit alive after the patient leaves the office. The demo tells this story in 4 acts:

1. **Record** — Patient records a visit (or uses demo recording). AI transcribes and structures the conversation into clinical notes.
2. **Understand** — Patient reads their visit summary with medical terms explained in plain language. AI chat answers any question about their visit.
3. **Track** — Patient monitors their health data (vitals, labs, documents) and can ask AI about any data point in relation to their visit.
4. **Loop In** — Doctor gets alerts when something needs attention (weight spike, BP trend, AI escalation). Doctor stays in the loop without extra work.

**Core value loop:** visit data + health data + documents + AI chat = personalized health understanding.

---

## 2. Architecture Decisions (Resolved Feb 11)

| Decision | Choice | Rationale |
|----------|--------|-----------|
| Scenario Picker UX | Separate page `/demo/scenarios` with patient cards | Clear, visual selection before entering demo |
| Chat architecture | **Hybrid** — two-column on Visit Detail, slide-in panel everywhere else | Chat is primary on Visit Detail, supplementary elsewhere |
| Chat icon | Polished message bubble / AI assistant icon, reusable component | Replaces ugly green `?` button. Consistent across all pages |
| Doctor actions | Messages = real. Schedule/renew/recommend = mock UI | Messages are the core loop; rest is demo showcase |
| Demo data persistence | Fresh user per session (default). Nice-to-have: "Resume previous session" option | Judges need isolation; returning to same session is bonus |
| Health Profile | Research FHIR/Apple Health/MyChart → implement based on findings | Cut from larger set rather than guess fields |
| Labs | Mock data for trends + **real PDF analysis via Opus** | AI extraction is the wow factor; mock data fills the charts |
| Labs photo upload | Delegated to separate agent | QR code scan from phone → AI OCR |

---

## 3. Priority Groups

### P0 — Demo Blockers
*Without these, the demo breaks or looks broken. Fix first.*

| # | Item | Screen | Type | Est |
|---|------|--------|------|-----|
| 1 | Remove PII "Dr. Ciarka" from AI-generated notes | Visit Detail | Bug fix | 30m |
| 2 | Fix medications in wrong SOAP sections (ScribeProcessor) | Visit Detail | Bug fix | 2-3h |
| 3 | AI Analysis auto-refresh when complete (polling/SSE) | Visit Detail | Bug fix | 1-2h |
| 4 | Patient search backend filtering (DoctorController) | Doctor Dashboard | Bug fix | 30m |
| 5 | "Try Demo" → go to `/login` page, NOT auto-login as patient | Login | Behavior fix | 30m |
| 6 | **Scenario Picker** — separate page, fresh user per session, config-driven scenarios | NEW screen | New feature | 4-6h |

**Total P0: ~10-13h**

---

### P1 — Core Demo Narrative
*Without these, the demo doesn't tell the story. Build after P0.*

| # | Item | Screen | Type | Est |
|---|------|--------|------|-----|
| 7 | Demo Access section — move higher, highlight/glow, entrance animation | Login | UI polish | 1-2h |
| 8 | Short visit summary at top (2-3 sentences, plain language) | Visit Detail | New section | 2-3h |
| 9 | Doctor's recommendations / action items section | Visit Detail | New section | 2-3h |
| 10 | Next actions checklist (bottom of visit) | Visit Detail | New section | 1-2h |
| 11 | **Two-column layout** — Visit Summary (left) + Chat (right), always visible | Visit Detail | Major refactor | 4-6h |
| 12 | **Chat slide-in panel** — reusable component, nice icon, context-aware | All pages | New component | 3-4h |
| 13 | **"Ask about this" buttons** on every SOAP section, attachments, AI analysis | Visit Detail | New component | 2-3h |
| 14 | Remove green floating `?` button — replace with new chat icon | Visit Detail | UI change | 30m |
| 15 | "Use Demo Recording" button — appears ~2s after recording starts | Companion Scribe | New UI | 1-2h |
| 16 | Quality gate — AI evaluates transcript content, fallback to demo if insufficient | Processing | New feature | 2-3h |
| 17 | Term Highlighter in AI Analysis (attachment results) | Visit Detail | Extension | 1-2h |
| 18 | Add suggested Q: "Can I drink alcohol with my medication?" | Chat | Config change | 15m |
| 19 | **Alert Panel** — "Requires Attention" at top of Doctor Dashboard | Doctor Dashboard | New section | 3-4h |
| 20 | Doctor → Patient messaging (already works, verify end-to-end) | Doctor Detail | Verification | 30m |

**Total P1: ~25-35h**

---

### P2 — Polish & Depth
*Makes the demo feel complete and professional. Build after P1.*

| # | Item | Screen | Type | Est |
|---|------|--------|------|-----|
| 21 | Visit titles — shorter, cleaner summaries | Visit List | UI fix | 1h |
| 22 | Layout reorder: Visits → Record → Health Dashboard → Library | Visit List | UI change | 1h |
| 23 | Patient cards — main condition, last visit, status badge, mini sparkline | Doctor Dashboard | UI upgrade | 3-4h |
| 24 | Quick action buttons per patient (View Visit, Review Chat, Send Message) | Doctor Dashboard | New UI | 1-2h |
| 25 | **Health Profile expansion** — research FHIR/standards → implement fields | My Health | Research + impl | 4-6h |
| 26 | Vitals & Labs — split into two separate tabs | My Health | Refactor | 2-3h |
| 27 | HRV chart (Heart Rate Variability) | My Health | New chart | 2h |
| 28 | Sleep information (duration, quality, stages) | My Health | New section | 2h |
| 29 | Weight trend — change to bar chart + average line + period delta | My Health | Chart refactor | 2h |
| 30 | All vitals: time range filter (7d / 30d / 90d / 1y) | My Health | New filter | 2-3h |
| 31 | Labs browser — trends per marker, reference ranges | My Health | New section | 3-4h |
| 32 | **Labs PDF upload + AI extraction** (real, via Opus) | My Health | New feature | 4-6h |
| 33 | Documents as DB entries, usable as chat context | My Health | Data model | 2-3h |
| 34 | Document icons and status badges — visual polish | My Health | UI polish | 1h |
| 35 | Attachments — AI Analysis expanded by default after completion | Visit Detail | UI fix | 30m |
| 36 | Attachments — "Analyzing document..." progress more visible | Visit Detail | UI polish | 1h |
| 37 | Shared documents from patient visible to doctor | Doctor Detail | New section | 2h |
| 38 | Doctor actions panel (schedule, renew, recommend — mock UI) | Doctor Detail | Mock UI | 2h |
| 39 | Alert detail view — click alert → full context | Doctor Detail | New view | 2h |
| 40 | Settings: Audit logs viewer (mock) | Settings | Mock UI | 2h |
| 41 | Settings: Document permissions (mock) | Settings | Mock UI | 1-2h |
| 42 | Per-profile library — each scenario has own conditions/meds/refs | Library | Data config | 2h |
| 43 | Custom documents: URL/PDF upload to library | Library | New feature | 2-3h |
| 44 | Mobile: tab switcher for chat (Summary / Chat) | Visit Detail | Responsive | 2h |
| 45 | "Ask about this" buttons on Health, Labs, Library sections → slide-in chat | All pages | Extension | 2-3h |

**Total P2: ~47-60h**

---

### P3 — Nice-to-Have
*Wow factor, if time allows. Don't start until P1 is complete.*

| # | Item | Screen | Est |
|---|------|--------|-----|
| 46 | Select any text → explain via AI (text selection popup) | Visit Detail | 3-4h |
| 47 | "Explain this" → highlight/glow Send button when explanation loads | Visit Detail | 1h |
| 48 | Pre-loaded sample document for attachment analysis demo | Visit Detail | 1-2h |
| 49 | Connected Services — visual polish, real brand logos | My Health | 2-3h |
| 50 | Connected Services — click → show mock data | My Health | 3-4h |
| 51 | Labs: photo/scan upload → AI OCR (delegated to separate agent) | My Health | 4-6h |
| 52 | AI Insights Summary panel (Doctor) | Doctor Dashboard | 2-3h |
| 53 | Patient timeline — unified chronological view | Doctor Detail | 3-4h |
| 54 | ElevenLabs audio scenarios (pre-recorded doctor-patient dialog) | Companion Scribe | 3-4h |
| 55 | Smoother processing animations (step-by-step with fluid transitions) | Processing | 2-3h |
| 56 | Notification bell / unread counter in Doctor layout | Doctor Layout | 1-2h |
| 57 | Demo data persistence — "Resume previous session" option | Scenario Picker | 2-3h |
| 58 | Background gradient polish (Landing) | Landing | 30m |
| 59 | AI-generated patient avatars (fictional faces, not real people) | All patient views | 1-2h |
| 60 | Auto-categorization of uploaded documents (EKG, Lab, Imaging, Rx, etc.) | Visit Detail | 1-2h |
| 61 | Auto-extraction of document date from content — critical for timeline context | Visit Detail | 1h |
| 62 | Demo mode banner — yellow bar at top showing "Demo Mode", current patient name, "Switch Scenario" button | All pages | 1-2h |
| 63 | Demo patient emails as readable names (alex.johnson@demo.postvisit.ai) instead of random hashes | Scenario Picker | 30m |

---

## 4. Implementation Schedule

### Day 1 (Feb 12) — Fix & Unblock
**Goal:** All P0 items done. Demo doesn't break.

- Morning: Bugs #1-5 (PII, SOAP, auto-refresh, search, Try Demo)
- Afternoon: Scenario Picker (#6) — architecture + implementation
- Evening: Verify all P0, deploy to production

### Day 2 (Feb 13) — Core Story
**Goal:** P1 items #7-18. Demo tells the story.

- Morning: Visit Detail — summary, recommendations, next actions (#8-10)
- Afternoon: Two-column layout + chat slide-in (#11-12, #14)
- Evening: "Ask about this" buttons, term highlighter, demo recording button (#13, #15, #17)

### Day 3 (Feb 14) — Core Story + Doctor
**Goal:** P1 items #16, #19-20 + start P2.

- Morning: Quality gate (#16), Alert Panel (#19), messaging verification (#20)
- Afternoon: P2 priority items — Health Profile research (#25), Vitals/Labs split (#26)
- Evening: P2 — Labs browser (#31), PDF upload (#32)

### Day 4 (Feb 15) — Polish & Ship
**Goal:** Remaining P2 items, final polish, demo rehearsal.

- Morning: Remaining P2 items (patient cards, charts, filters, documents)
- Afternoon: Settings mock, Library per-profile, mobile responsive
- Evening: Full demo rehearsal, bug fixes, deploy final version

### Day 5 (Feb 16) — Submission Day
**Goal:** Final testing, documentation, submit.

- Morning: Final E2E test, fix last issues
- Noon: Record demo video if needed
- 15:00 EST: Deadline

---

## 5. Scenario Picker — Architecture

### Flow
```
"Sign in as Patient" → /demo/scenarios → pick scenario card →
  → POST /api/v1/demo/start-scenario {scenario: "pvcs"} →
  → Creates fresh user (demo-pvcs-abc123@postvisit.ai) →
  → Seeds visits, notes, observations, conditions, prescriptions →
  → Logs user in → redirect to /profile
```

### Scenarios (MVP)
1. **PVCs / Palpitations** — Alex Johnson, 38M, cardiology. Propranolol 40mg 2x/day. Existing data.
2. **Heart Failure** — Maria Santos, 67F, cardiology. Weight monitoring, fluid retention alerts. Existing in seeder.
3. *(More added later by Nedo)*

### Config-driven architecture
```php
// config/demo-scenarios.php
return [
    'pvcs' => [
        'patient' => ['first_name' => 'Alex', 'last_name' => 'Johnson', ...],
        'visits' => [...],
        'observations' => [...],
        'conditions' => [...],
        'prescriptions' => [...],
    ],
    'heart-failure' => [...],
];
```

Adding a new scenario = adding a new array entry. No code changes.

### Cleanup
- Cron job: delete demo users older than 24h
- `php artisan demo:cleanup` command

---

## 6. Chat Architecture — Hybrid Model

### Visit Detail: Two-Column
```
┌─────────────────────────────┬──────────────────┐
│  Visit Summary (scrollable) │  AI Chat (fixed)  │
│                             │                   │
│  [Short Summary]            │  Suggested Qs     │
│  [SOAP Sections]            │  ─────────────    │
│  [Recommendations]          │  Chat messages    │
│  [Next Actions]             │  ...              │
│  [Attachments]              │  ─────────────    │
│                             │  [Input] [Send]   │
└─────────────────────────────┴──────────────────┘
```

### All Other Pages: Slide-In Panel
```
┌─────────────────────────────────────────────┐
│  Page Content                    [Chat Icon] │
│  ...                                         │
│  [Section] ──── [Ask about this]             │
│  ...                                         │
└─────────────────────────────────────────────┘

Click "Ask about this" or Chat Icon:

┌──────────────────────┬──────────────────────┐
│  Page Content        │  AI Chat (slide-in)  │
│  (compressed)        │  Context: [section]  │
│                      │  ─────────────       │
│                      │  Chat messages       │
│                      │  ─────────────       │
│                      │  [Input] [Send]      │
└──────────────────────┴──────────────────────┘
```

### Mobile: Tab Switcher
```
┌─────────────────────────────┐
│  [Summary] [Chat]  ← tabs   │
│─────────────────────────────│
│  Active tab content          │
│  ...                         │
│                              │
└─────────────────────────────┘
```

### Context Passing
"Ask about this" buttons pass context object to chat:
```javascript
openChat({
  source: 'visit-detail',        // or 'health', 'library', etc.
  section: 'assessment',          // SOAP section, vital type, lab marker
  context: 'Assessment text...',  // actual content for AI context
  visitId: '...',                 // optional visit reference
})
```

---

## 7. Doctor-in-the-Loop — Alert System

### Alert Sources (already in codebase)
1. **Weight gain alert** — WeightChart.vue already calculates: if weight gain ≥ 2kg in 3 days → alert. Need to surface this to dashboard.
2. **BP trend alert** — BloodPressureChart.vue tracks elevated readings. Need threshold → alert.
3. **AI escalation** — EscalationDetector service flags dangerous symptoms in chat. Need to surface to doctor notifications.

### Dashboard Alert Panel
```
┌─ Requires Your Attention ───────────────────┐
│ 🔴 Alex Johnson: +2.3kg in 3 days (HF)     │
│    Weight: 85.2 → 87.5kg  [View Details]    │
│                                              │
│ 🟡 Maria Santos: BP 158/95 — 3x elevated   │
│    Last 3 readings above Stage 1  [View]    │
└──────────────────────────────────────────────┘
```

### Doctor Response Actions
- **Send message** (real — already works)
- Schedule follow-up (mock — toast confirmation)
- Renew prescription (mock — toast confirmation)
- Add recommendation (mock — toast confirmation)

---

## 8. Dependencies & Parallel Work

```
P0 Bugs (#1-5) ──────────────┐
                              ├──→ P1 Core (#7-20)
Scenario Picker (#6) ────────┘         │
                                       ├──→ P2 Polish (#21-45)
Health Profile Research (#25) ─────────┘         │
                                                 ├──→ P3 Nice (#46-58)
Labs PDF Analysis (#32) ────────────────────────┘

Independent parallel tracks:
- Frontend: Two-column layout (#11) || Backend: Quality gate (#16)
- Frontend: Chat slide-in (#12) || Backend: Scenario Picker (#6)
- Frontend: Alert Panel (#19) || Backend: SOAP fix (#2)
- Research: Health Profile (#25) runs in background
```

---

## 9. Files Reference

| Document | Purpose |
|----------|---------|
| `docs/demo-prd.md` | This file — implementation plan for demo |
| `docs/demo-checklist.md` | Detailed element-by-element review of every screen |
| `docs/prd.md` | Original PRD (historical, foundational requirements) |
| `docs/data-model.md` | Database schema source of truth |
| `docs/decisions.md` | Architecture decisions log |
| `CLAUDE.md` | Agent coding guidelines and project conventions |
