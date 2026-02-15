# Demo E2E Checklist — Focus Run 12/2

**Goal:** Review the entire demo flow, categorize each element as MUST/NICE/SKIP.
**Date:** 2026-02-12
**Last audit:** 2026-02-12 (code audit vs main)
**Deadline:** 16/2, 21:00 CET

---

## Implementation Run: 13 Must-Haves (2026-02-12 evening)

### Implemented (6 parallel agents)

| #     | Item                                                           | Status | Verified |
|-------|----------------------------------------------------------------|--------|----------|
| MH-1  | Ask button → top-right in LabResultsTab + global Ask           | DONE   | [ ]      |
| MH-2  | Lab charts verified (4 readings per marker in demo data)       | DONE   | [ ]      |
| MH-3  | Lab results uploader (PDF/photo) with drag-drop                | DONE   | [ ]      |
| MH-4  | Agents tab in My Health — API mockup, token gen, MCP config    | DONE   | [ ]      |
| MH-5  | Documents tab rebuilt — real backend, upload, AI analysis      | DONE   | [ ]      |
| MH-6  | All visit section icons unified to emerald                     | DONE   | [ ]      |
| MH-7  | Audio playback section + backend streaming endpoint            | DONE   | [ ]      |
| MH-8  | Chat suggestions randomized from expanded pools, fallback badge| DONE   | [ ]      |
| MH-9  | Ask EBM tab removed from Reference                             | DONE   | [ ]      |
| MH-10 | Clinical References add/remove with localStorage               | DONE   | [ ]      |
| MH-11 | My Library seeded with 4 copyright-friendly demo items         | DONE   | [ ]      |
| MH-13 | Reference/condition/medication chat suggestions                | DONE   | [ ]      |
| MH-17 | Legal links (Terms, Privacy, Legal Notice) in Settings         | DONE   | [ ]      |

### Post-implementation fixes

| Fix | Description                                                    | Status |
|-----|----------------------------------------------------------------|--------|
| F-1 | Agents moved from top nav → My Health tab                      | DONE   |
| F-2 | Key Findings in Documents: formatted cards instead of raw JSON | DONE   |
| F-3 | Ask button moved to right side + global Ask in Lab Results     | DONE   |

### Backlog (nice-to-haves from this run)

| #  | Item                                                | Status  |
|----|-----------------------------------------------------|---------|
| N5 | Ask button hover → highlights context card           | Pending |
| N6 | Lab Results — QR code / mobile phone upload          | Pending |
| N7 | My Library — document parsing/caching for context    | Pending |

### Build & Tests
- `bun run build` — passes
- `herd php artisan test` — 231 passed (679 assertions)

---

## Screen 1: Landing Page (`/`)

| Element | Status | Category | Notes |
|---------|--------|----------|-------|
| PostVisit.ai title + tagline | DONE | MUST | |
| "Try Demo" button | DONE | MUST | |
| "Sign In" button | DONE | MUST | |
| Medical disclaimer | DONE | MUST | |
| Background gradient | DONE | NICE | |

---

## Screen 2: Login Page (`/login`)

| Element | Status | Category | Notes |
|---------|--------|----------|-------|
| "Try Demo" → goes to /login (NOT auto-login) | DONE | MUST | Fixed — goes to /login now |
| Demo Access section — move higher, more prominent | DONE | MUST | Above email/password form |
| Demo Access — highlight/glow effect | DONE | MUST | Emerald glow animation |
| Demo Access — entrance animation | DONE | MUST | Subtle animation on load |
| "Sign in as Patient" button | DONE | MUST | |
| "Sign in as Doctor" button | DONE | MUST | |
| Email/Password form | DONE | MUST | |
| "Sign up" link | DONE | NICE | |

---

## Screen 2.5: Patient Scenario Picker

| Element | Status | Category | Notes |
|---------|--------|----------|-------|
| Scenario selection screen after "Sign in as Patient" | DONE | MUST | `/demo/scenarios` route + ScenarioPicker.vue |
| 12 patient scenarios across specialties | DONE | MUST | 12 scenarios in config/demo-scenarios.php |
| Each scenario: name, age, short description, condition | DONE | MUST | |
| Fresh user per demo session (isolation) | DONE | MUST | Fresh user created per session |
| Scenario creates user + visits + labs + notes on click | DONE | MUST | Config-driven, DemoSeeder handles all data |
| 4 featured scenarios + "Show more" for rest | DONE | MUST | Featured cards prominent, rest behind expander |
| Specialty filter pills | DONE | MUST | Filter by specialty |
| Fibromyalgia (Fatima Benali) scenario | BUG | MUST | Config exists but "Error loading scenario" — known bug |

---

## Screen 3: Patient — Visit List (Profile)

| Element | Status | Category | Notes |
|---------|--------|----------|-------|
| Visit History — primary element | DONE | MUST | |
| Visit titles — shorter, cleaner | TODO | MUST | Still showing full AI chief complaint text |
| Record New Visit — right below Visit History | DONE | MUST | |
| Layout order: Visits → Record → Health Dashboard → Library | TODO | MUST | Reorder: primary actions first |
| Health Dashboard link | DONE | NICE | |
| Library link | DONE | NICE | |
| User avatar + name + email | DONE | NICE | |
| Fictional data disclaimer | DONE | MUST | |

---

## Screen 4: Patient — Visit Detail

| Element | Status | Category | Notes |
|---------|--------|----------|-------|
| Visit Summary header (date, type, doctor) | DONE | MUST | |
| **Short visit summary at top** (2-3 sentences, plain language) | DONE | MUST | Gradient card at top of visit |
| SOAP sections (CC, HPI, Symptoms, Exam, Assessment, Plan) | DONE | MUST | Collapsible, working |
| Medical term highlighting (green underline) | DONE | MUST | Working — terms clickable |
| "Explain this" button per section | DONE | MUST | |
| **"Explain this" → highlight Send button** visually | DONE | MUST | `animate-send-glow` on Send button + chat flash animation |
| Select any text → explain via AI | TODO | NICE | User selects arbitrary text, popup offers "Explain this" |
| AI-Extracted Clinical Entities | DONE | NICE | |
| Visit Transcript | DONE | NICE | |
| **Doctor's recommendations / action items** | DONE | MUST | Numbered items parsed from plan text, with AskAiButton |
| **Next actions checklist** | DONE | MUST | Interactive checkboxes with strikethrough, derived from follow_up + prescriptions |
| **Attachments — AI Analysis auto-refresh** | DONE | MUST | Polling in VisitAttachments.vue |
| **Attachments — AI Analysis expanded by default** | TODO | MUST | Should show findings immediately, not collapsed |
| **Attachments — "Analyzing document..." progress** more visible | TODO | MUST | Currently tiny spinner, no sense of progress |
| Attachments — Upload files drop zone too large | OK | NICE | |
| **Demo: pre-loaded sample document** | TODO | NICE | Sample lab report PDF or ECG report for demo |
| **Auto-categorization of uploaded documents** | TODO | NICE | AI assigns category badge |
| **Auto-extraction of document date** | TODO | NICE | AI extracts date from document content |
| Remove PII from AI-generated notes | DONE | MUST | "Dr. Ciarka" removed |
| Chat button bottom-right | DONE | MUST | Floating action button |
| **"Ask about this" button on EVERY section** | DONE | MUST | AskAiButton on all SOAP sections, recommendations, next actions, observations, diagnosis, meds, entities, transcript |
| **Term Highlighter in AI Analysis (attachments)** | DONE | MUST | HighlightedText + matchTermsInText() in VisitAttachments.vue |

## Screen 5: Patient — Chat

| Element | Status | Category | Notes |
|---------|--------|----------|-------|
| **Two-column layout: Visit Summary (left) + Chat (right)** | DONE | MUST | Working on desktop, visit scrolls independently |
| **Mobile: tab switcher** ("Summary" / "Chat") or bottom sheet | TODO | MUST | Single column on mobile, needs toggle |
| Green floating ? button — remove or redesign | DONE | MUST | Redesigned — emerald chat icon with pulse dot |
| Suggested questions (5 quick-picks) | DONE | MUST | diagnosis, meds, watch out, call doctor, alcohol |
| **Add suggested Q: "Can I drink alcohol with my medication?"** | DONE | MUST | Added to suggestions |
| **Section-specific "Ask about this" buttons** feed context into chat | DONE | MUST | AskAiButton passes section context to chat |
| Chat streaming response (SSE) | DONE | MUST | |
| Chat input field + Send | DONE | MUST | |

---

## Screen 6: Companion Scribe (Recording)

| Element | Status | Category | Notes |
|---------|--------|----------|-------|
| Microphone permission + recording starts | DONE | MUST | |
| Timer / recording indicator | DONE | MUST | |
| Stop recording button | DONE | MUST | |
| **"Use Demo Recording" button** | DONE | MUST | In CompanionScribe.vue |
| Demo data loads pre-recorded transcript + processes it | DONE | MUST | Via useDemoTranscript() |
| Real recording → upload → Whisper transcription | DONE | MUST | Working with retry logic |
| Process Visit after recording stops | DONE | MUST | |
| **Playback pre-recorded audio scenarios** | TODO | NICE | Picks scenario, audio plays, Whisper transcribes live |
| Generate audio scenarios via ElevenLabs TTS | DONE | NICE | Audio files in demo/visits/visit-XX-*/dialogue-tts.mp3 |

---

## Screen 7: Processing / Analyzing Visit

| Element | Status | Category | Notes |
|---------|--------|----------|-------|
| Processing screen shows progress | DONE | MUST | |
| **Quality gate after transcription** | DONE | MUST | TranscriptController validates clinical content |
| Fallback to demo transcript preserves same flow | DONE | MUST | |
| **Smoother, more polished animations** | TODO | NICE | Step-by-step fluid transitions |

---

## Screen 8: Doctor — Dashboard

| Element | Status | Category | Notes |
|---------|--------|----------|-------|
| Stats cards (Patients, Unread Messages, Total Visits) | DONE | MUST | |
| Patient list with search | DONE | MUST | |
| **Patient search — backend filtering** | DONE | MUST | DoctorController filters by name |
| **Alert Panel — "Requires Attention"** at top | DONE | MUST | Weight/BP trend alerts |
| **Patient cards — richer info** (condition, status, vitals) | DONE | MUST | Condition badge, alert status color, last vitals, visits count |
| **Patient cards — mini sparklines** | TODO | MUST | Tiny inline chart of last 7 weight/BP readings |
| **Quick action buttons per patient** | DONE | MUST | Follow-up, Prescription, Message + More dropdown |
| **AI Insights Summary panel** | TODO | NICE | AI oversight stats for doctor |
| **Notification bell / unread counter** | TODO | NICE | In DoctorLayout header |

---

## Screen 9: Doctor — Patient Detail

| Element | Status | Category | Notes |
|---------|--------|----------|-------|
| Patient profile card (name, age, conditions) | DONE | MUST | |
| Weight monitoring chart (clinical-grade SVG) | DONE | MUST | Dry weight ref, alert threshold, color-coded |
| Blood pressure chart (systolic + diastolic) | DONE | MUST | Clinical thresholds, elevated count |
| Visit history list | DONE | MUST | |
| AI Chat Audit Trail (expandable sessions) | DONE | MUST | Full transcript, color-coded |
| Notifications / Messages with inline reply | DONE | MUST | Bidirectional messaging |
| **Shared documents from patient** | TODO | MUST | Patient-uploaded documents visible to doctor |
| **Patient health data — reuse patient-side components** | PARTIAL | MUST | Weight + BP charts reused; missing Vitals/Labs tabs |
| **Doctor actions panel** | TODO | MUST | Schedule, renew, recommend — even mock UI |
| **Alert detail view** — click from dashboard | PARTIAL | MUST | Links to patient page but no scroll/highlight to alert |
| **Patient timeline** — unified chronological view | TODO | NICE | Combine visits, vitals, chat, messages |
| **Follow-up / Prescription / Message actions work** | TODO | NICE | Buttons present but show "coming soon" toast |

---

## Screen 10: Health Dashboard (My Health)

| Element | Status | Category | Notes |
|---------|--------|----------|-------|
| Health Profile tab — Personal Info, Biometrics, Allergies, Emergency Contact | DONE | MUST | |
| **Health Profile — expand significantly** | PARTIAL | MUST | Basics done; missing chronic conditions, surgical/family history, smoking |
| **Research task: standardized health record formats** | TODO | MUST | FHIR, Apple Health, Epic MyChart, OpenEHR |
| Connected Services tab | DONE | NICE | 14 services, categories good |
| **Connected Services — visual polish** | TODO | NICE | Real brand logos, more professional design |
| **Connected Services — click → show mock data** | TODO | NICE | Click Apple Health → mock HR, steps, sleep |
| **Vitals & Labs — split into two separate tabs** | DONE | MUST | VitalsTab + LabResultsTab |
| **Vitals tab:** BP, HR, Weight charts | DONE | MUST | Working with Chart.js |
| **Add: HRV chart** | TODO | MUST | Heart Rate Variability — important for cardiac patients |
| **Add: Sleep information** | TODO | MUST | Duration, quality, stages |
| **Weight Trend — bar chart + avg line + delta** | PARTIAL | MUST | Doctor-side has bar chart; patient-side still line chart |
| **All vitals: time range filter** (7d/30d/90d/1y) | DONE | MUST | Buttons in VitalsTab, cutoffDate computed |
| **Labs tab:** browser with trends per marker | PARTIAL | MUST | List with values/ranges/coloring; no per-marker trend charts |
| **Labs: connect to lab provider** | TODO | NICE | Quest, Labcorp integration |
| **Labs: upload results** — PDF + AI extraction | TODO | MUST | Upload PDF → AI extracts values → trends |
| **Labs: photo/scan upload** → AI OCR | TODO | NICE | Photo of paper results → AI extraction |
| Apple Watch summary card | DONE | MUST | |
| Blood Pressure Trend chart | DONE | MUST | |
| Heart Rate Trend chart | DONE | MUST | |
| **Lab Results & Vitals: term explainer (tap-to-explain)** | TODO | MUST | Medical term highlighter on lab/vital names |

### Cross-cutting: Context → Chat

| Requirement | Status | Notes |
|-------------|--------|-------|
| Every health element selectable as chat context | TODO | MUST — "Ask about this" on vitals, labs, meds, allergies |

### Documents Tab (inside My Health)

| Element | Status | Category | Notes |
|---------|--------|----------|-------|
| Documents list — icons look poor | TODO | MUST | Need proper file type icons |
| Documents list — status badges look mockup-ish | TODO | MUST | Need polished design |
| **Each document = database entry** | DONE | MUST | Document model exists, stored in DB |
| **Each document usable as chat context** | DONE | MUST | ContextAssembler loads library items; ChatPanel has Documents context source |
| **Upload/add documents from Documents tab** | TODO | MUST | User can upload PDFs, photos from My Health → Documents |
| **Mobile: upload via camera/photos (no QR)** | TODO | MUST | On mobile, skip QR code — direct photo/file upload from phone |

---

## Cross-cutting: AI Chat Assistant on EVERY Page (CRITICAL MUST)

| Requirement | Status | Category | Notes |
|-------------|--------|----------|-------|
| **AI Chat accessible from every page** | DONE | MUST | Global ChatPanel in PatientLayout.vue — fixed right panel on desktop, floating button + slide-over on mobile |
| **Context selection from any section** | PARTIAL | MUST | Works on Visit Detail (SOAP sections); NOT yet on health/labs/library |
| **"Ask about this" button on every data element** | PARTIAL | MUST | Done on Visit Detail; NOT done on health/labs/library sections |
| **Chat panel architecture** | DONE | MUST | Reusable ChatPanel.vue, works across all patient pages |

---

## Screen 11: Medical Library

| Element | Status | Category | Notes |
|---------|--------|----------|-------|
| Your Conditions (auto-populated from visits) | DONE | MUST | |
| Your Medications (auto-populated) | DONE | MUST | |
| Clinical References (PubMed Verified) | DONE | MUST | |
| "Relevant for You" / "Ask AI" / "Search Databases" tabs | DONE | MUST | |
| OpenEvidence Integration | COMING SOON | NICE | |
| **Add custom documents: URL, PDF upload** | DONE | MUST | "My Library" tab with drag-drop PDF + URL input |
| **Per-profile library** — each scenario has own data | DONE | MUST | Loads per-patient conditions/meds/refs |
| Search Databases tab — ICD-10 only | WEAK | SKIP | |

---

## Screen 12: Settings

| Element | Status | Category | Notes |
|---------|--------|----------|-------|
| **Audit Logs viewer** | DONE | MUST | Patient-side mock + doctor-side real (DoctorAuditLog.vue with filters, CSV export) |
| **Document permissions** | DONE | MUST | Data Governance section with toggles (share docs, AI analysis, care team) |
| **System logs viewer** | TODO | NICE | |
| Profile settings (name, email, password) | TODO | NICE | |

---

## Bugs Found During Review

| # | Screen | Bug | Status | Severity |
|---|--------|-----|--------|----------|
| 1 | Visit Detail | "Dr. Ciarka" — real PII in notes | FIXED | HIGH |
| 2 | Visit Detail | Meds in wrong SOAP sections | TODO | HIGH |
| 3 | Visit Detail | Physical Exam nonsensical content | TODO | HIGH |
| 4 | Visit Detail | AI Analysis no auto-refresh | FIXED | HIGH |
| 5 | Doctor Dashboard | Patient search doesn't filter | FIXED | LOW |
| 6 | Scenario Picker | Fibromyalgia "Error loading scenario" | BUG | HIGH |

---

## Summary (Updated 2026-02-12)

### Status Counts
- **MUST items DONE:** ~45 (landing, login, scenario picker, visit detail SOAP + summary + recommendations + next actions + term highlighting + ask-about-this, chat two-column + suggestions + streaming, recording + quality gate, doctor dashboard alerts + patient cards + search + actions, health vitals/labs split + time filter, library custom docs + per-profile, settings audit + permissions, global chat panel)
- **MUST items TODO:** ~20 (visit titles shorter, profile layout reorder, mobile chat tab, health profile expansion, HRV chart, sleep info, weight bar chart patient-side, labs trend charts, labs PDF upload, "ask about this" on health/labs/library, sparklines, doctor actions panel, shared docs to doctor, alert detail view, document icons, analyzing progress, SOAP bug #2/#3, Fatima bug, upload docs from My Health, mobile camera/file upload without QR)
- **MUST items PARTIAL:** 5 (patient health in doctor view, alert detail, weight chart, labs browser, health profile expansion)
- **NICE items:** ~12 (text selection explain, connected services polish, ElevenLabs playback, smoother animations, photo scan upload, patient timeline, AI insights, notification bell, connected services mock data)
- **SKIP items:** 1 (Search Databases ICD-10 only)

### Bugs: 3 fixed, 3 remaining
