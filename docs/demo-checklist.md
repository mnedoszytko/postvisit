# Demo E2E Checklist — Focus Run 12/2

**Cel:** Przejrzeć cały demo flow, skategoryzować każdy element jako MUST/NICE/SKIP.
**Data:** 2026-02-12
**Deadline:** 16/2, 21:00 CET

---

## Screen 1: Landing Page (`/`)

| Element | Status | Category | Notes |
|---------|--------|----------|-------|
| PostVisit.ai title + tagline | OK | MUST | |
| "Try Demo" button | OK | MUST | |
| "Sign In" button | OK | MUST | |
| Medical disclaimer | OK | MUST | |
| Background gradient | OK | NICE | |

---

## Screen 2: Login Page (`/login`)

| Element | Status | Category | Notes |
|---------|--------|----------|-------|
| "Try Demo" → goes to /login (NOT auto-login) | BROKEN | MUST | Currently auto-logs as patient, should show login page |
| Demo Access section — move higher, more prominent | TODO | MUST | Should be above or equal to email/password form |
| Demo Access — highlight/glow effect | TODO | MUST | More visually prominent than regular login |
| Demo Access — entrance animation | TODO | MUST | Subtle animation when page loads |
| "Sign in as Patient" button | OK | MUST | |
| "Sign in as Doctor" button | OK | MUST | |
| Email/Password form | OK | MUST | Keep as-is |
| "Sign up" link | OK | NICE | |

---

## Screen 2.5: Patient Scenario Picker (NEW)

| Element | Status | Category | Notes |
|---------|--------|----------|-------|
| Scenario selection screen after "Sign in as Patient" | TODO | MUST | Pick your patient profile before entering |
| 3 patient scenarios: PVCs/palpitations, Heart Failure, Hypertension | TODO | MUST | Each = fresh user + visit data |
| Each scenario: name, age, short description, condition | TODO | MUST | |
| Fresh user per demo session (isolation) | TODO | MUST | Multiple judges can't corrupt each other's data |
| Scenario creates user + visits + labs + notes on click | TODO | MUST | Replaces current shared Alex Johnson |

**Architecture note:** Each "Sign in as Patient" click → scenario picker → creates a new user (e.g. `demo-pvcs-abc123@postvisit.ai`), seeds their data, logs them in. Old demo users cleaned up via cron.

**Scenarios (MVP):**
1. PVCs/Palpitations (existing — Alex Johnson cardiology case)
2. Heart Failure (existing in seeder — HF case)
3. (more added later by Nedo)

**Architecture must support:** adding new scenarios = just adding a new array/config entry with patient data + visit notes. No code changes needed per scenario.

## Screen 3: Patient — Visit List (Profile)

| Element | Status | Category | Notes |
|---------|--------|----------|-------|
| Visit History — primary element | OK-ish | MUST | Titles too long, need shorter summaries |
| Visit titles — shorter, cleaner | TODO | MUST | Currently showing full AI chief complaint text |
| Record New Visit — right below Visit History | OK | MUST | Already there but after Health Dashboard |
| Layout order: Visits → Record → Health Dashboard → Library | TODO | MUST | Reorder: primary actions first |
| Health Dashboard link | OK | NICE | Move to bottom |
| Library link | OK | NICE | Move to bottom |
| User avatar + name + email | OK | NICE | |
| Fictional data disclaimer | OK | MUST | Already present at bottom |

---

## Screen 4: Patient — Visit Detail

| Element | Status | Category | Notes |
|---------|--------|----------|-------|
| Visit Summary header (date, type, doctor) | OK | MUST | |
| **Short visit summary at top** (2-3 sentences, what the visit was about) | TODO | MUST | Before SOAP sections, plain language for patient |
| SOAP sections (CC, HPI, Symptoms, Exam, Assessment, Plan) | OK | MUST | Collapsible, working |
| Medical term highlighting (green underline) | OK | MUST | Working — terms clickable |
| "Explain this" button per section | OK | MUST | |
| **"Explain this" → highlight Send button** visually when explanation loads | TODO | MUST | Send button should glow/colorize to draw attention to click it |
| Select any text → explain via AI | TODO | NICE | User selects arbitrary text, popup offers "Explain this" |
| AI-Extracted Clinical Entities | OK | NICE | |
| Visit Transcript | OK | NICE | |
| **Doctor's recommendations / action items** (at bottom of SOAP, before extras) | TODO | MUST | What the doctor told the patient to DO — meds, follow-ups, lifestyle |
| **Next actions checklist** (very bottom) | TODO | MUST | Actionable items: "Schedule follow-up in 2 weeks", "Take propranolol 2x/day", etc. |
| **Attachments — AI Analysis auto-refresh** when analysis completes (no manual reload) | BUG | MUST | Currently requires page refresh to see results |
| **Attachments — AI Analysis expanded by default** after completion | TODO | MUST | Should show findings immediately, not collapsed |
| **Attachments — "Analyzing document..." progress** more visible, bigger, with animation | TODO | MUST | Currently tiny spinner, no sense of progress |
| Attachments — Upload files drop zone too large | OK | NICE | Smaller would look cleaner |
| **Demo: pre-loaded sample document** for attachment analysis | TODO | NICE | E.g. sample lab report PDF or ECG report — so demo user can see AI analysis without uploading their own file |
| Remove PII from AI-generated notes | TODO | MUST | "Dr. Ciarka" and any other real names |
| Chat button (?) bottom-right | OK | MUST | Floating action button |
| **"Ask about this" button on EVERY section** — visible without expanding | TODO | MUST | Each SOAP section, Attachments, AI Analysis — all need a quick chat entry point. Click → opens chat pre-filled with context of that section |
| **Term Highlighter in AI Analysis (attachments)** | MISSING | MUST | Attachment analysis results have no highlighted terms — same TermExtractor should run on AI Analysis text |

## Screen 5: Patient — Chat

| Element | Status | Category | Notes |
|---------|--------|----------|-------|
| **Two-column layout: Visit Summary (left) + Chat (right)** | TODO | MUST | Like Claude Cowork. Chat always visible on desktop, no hidden button. Visit scrolls independently. |
| **Mobile: tab switcher** ("Summary" / "Chat") or bottom sheet | TODO | MUST | Single column on mobile, easy toggle between views |
| Green floating ? button — remove or redesign | TODO | MUST | Current button is invisible and ugly |
| Suggested questions (4 quick-picks) | OK | MUST | Already good: diagnosis, meds, watch out, call doctor |
| **Add suggested Q: "Can I drink alcohol with my medication?"** | TODO | MUST | Common real patient question, great demo showcase |
| **Section-specific "Ask about this" buttons** feed context into chat | TODO | MUST | Clicking "Ask about this" on Assessment → chat opens pre-loaded with that section's context |
| Chat streaming response (SSE) | OK | MUST | Already works |
| Chat input field + Send | OK | MUST | |

---

## Screen 6: Companion Scribe (Recording)

| Element | Status | Category | Notes |
|---------|--------|----------|-------|
| Microphone permission + recording starts | OK | MUST | Working |
| Timer / recording indicator | OK | MUST | |
| Stop recording button | OK | MUST | |
| **"Use Demo Recording" button** — appears ~2s after recording starts, below Stop | TODO | MUST | Fades in after 2s delay. Clicking it stops recording, loads demo transcript, goes straight to processing |
| Demo data loads pre-recorded transcript + processes it | OK | MUST | Already works via useDemoTranscript() |
| Real recording → upload → Whisper transcription | OK | MUST | Working (with retry logic) |
| Process Visit after recording stops | OK | MUST | |
| **Playback pre-recorded audio scenarios** — user picks a scenario, audio plays, system transcribes it live | TODO | NICE | Shows real Whisper transcription working on realistic audio |
| Generate audio scenarios via ElevenLabs TTS | TODO | NICE | Nedo writes scripts → ElevenLabs generates doctor-patient dialog audio → stored in demo/audio/ |

---

## Screen 7: Processing / Analyzing Visit

| Element | Status | Category | Notes |
|---------|--------|----------|-------|
| Processing screen shows progress | OK | MUST | Currently working |
| **Quality gate after transcription** — AI evaluates if transcript has enough clinical content | TODO | MUST | If insufficient data (e.g. 10s of "hello") → don't generate garbage visit → offer fallback: "Not enough clinical data. Use demo recording instead?" with scenario picker |
| Fallback to demo transcript preserves same flow (processing → visit) | TODO | MUST | User picks a demo scenario, system uses that transcript, continues processing normally |
| **Smoother, more polished animations** — step-by-step with fluid transitions | TODO | NICE | Better visual feedback: processing transcript → generating notes → extracting terms → done |

---

## Screen 8: Doctor — Dashboard

**Philosophy:** Doctor panel is NOT the focus — patient side is. Doctor view = "Doctor in the Loop" — see alerts, review trends, contact patient. Reuse patient-side components (charts, health data) where possible.

| Element | Status | Category | Notes |
|---------|--------|----------|-------|
| Stats cards (Patients, Unread Messages, Total Visits) | OK | MUST | Working but visually plain |
| Patient list with search | OK | MUST | Working, search UI present (backend filter missing) |
| **Patient search — backend filtering** | BUG | MUST | Vue sends search param but DoctorController doesn't filter by it |
| **Alert Panel — "Requires Attention"** at top of dashboard | TODO | MUST | Above patient list. Shows clinical alerts: weight gain >2kg/3d (HF), elevated BP streak, AI escalations. Data/logic already exists in WeightChart + EscalationDetector |
| **Patient cards — richer info** | TODO | MUST | Replace plain list with cards: main condition + severity badge, last visit date, status (Stable/Needs Review/Alert), engagement summary |
| **Patient cards — mini sparklines** | TODO | MUST | Tiny inline chart of last 7 weight/BP readings per patient — visual trend at a glance |
| **Quick action buttons per patient** | TODO | MUST | "View Latest Visit", "Review AI Chat", "Send Message" — one click to action |
| **AI Insights Summary panel** | TODO | NICE | "47 questions answered by AI this week", "3 escalations flagged", "Most common topics: side effects, diet" — doctor oversight of AI without reading every chat |
| **Notification bell / unread counter** in layout header | TODO | NICE | Real-time or polling-based unread count in DoctorLayout |

---

## Screen 9: Doctor — Patient Detail

| Element | Status | Category | Notes |
|---------|--------|----------|-------|
| Patient profile card (name, age, conditions) | OK | MUST | Working |
| Weight monitoring chart (clinical-grade SVG) | OK | MUST | Working — dry weight ref, alert threshold, color-coded |
| Blood pressure chart (systolic + diastolic) | OK | MUST | Working — clinical thresholds, elevated count |
| Visit history list | OK | MUST | Working |
| AI Chat Audit Trail (expandable sessions) | OK | MUST | Working — full transcript, color-coded messages |
| Notifications / Messages with inline reply | OK | MUST | Working — bidirectional messaging |
| **Shared documents from patient** | TODO | MUST | Documents/files patient shares with doctor — viewable, usable as context |
| **Patient health data — reuse patient-side components** | TODO | MUST | Labs, vitals, health profile — same data views as patient sees, reuse components (BP chart, weight chart already done) |
| **Doctor actions panel** | TODO | MUST | "Send message", "Schedule follow-up", "Renew prescription", "Add recommendation" — doctor-in-the-loop response actions |
| **Alert detail view** — when clicking alert from dashboard | TODO | MUST | Shows full context: what triggered alert, relevant data trend, suggested action |
| **Patient timeline** — unified chronological view | TODO | NICE | Single timeline combining: visits, abnormal vitals, AI chat sessions, messages — instead of separate sections |

---

## Screen 10: Health Dashboard (My Health)

| Element | Status | Category | Notes |
|---------|--------|----------|-------|
| Health Profile tab — Personal Info, Biometrics, Allergies, Emergency Contact | OK | MUST | Working but too basic |
| **Health Profile — expand significantly** | TODO | MUST | Research standardized health records (FHIR Patient, US Core, Apple Health). Add: chronic conditions, surgical history, family history, current medications list, immunizations, implants/devices, smoking/alcohol status |
| **Research task: standardized health record formats** | TODO | MUST | Look at FHIR Patient resource, Apple Health categories, Epic MyChart fields, OpenEHR archetypes — find the right level of detail for a patient-facing app |
| Connected Services tab — structure exists (Wearables, EHR, Pharmacies, Labs, Insurance) | OK | NICE | Categories good, 14 services listed |
| **Connected Services — visual polish** | TODO | NICE | Use real brand logos (mockup OK), more professional card design, less generic look |
| **Connected Services — click into connected service → show mock data** | TODO | NICE | E.g. click Apple Health → show mock heart rate, steps, sleep data. Click Epic MyChart → show mock records/labs. All mockup but demonstrates the integration concept |
| **Vitals & Labs — split into two separate tabs** (Vitals / Labs) | TODO | MUST | Currently combined, should be separate |
| **Vitals tab:** BP trend, HR trend, HRV (add), Weight (bar chart + avg + delta), Sleep (add) | TODO | MUST | Weight = bar chart with average line + period change info. All vitals need time range filter (7d/30d/90d/1y) |
| **Labs tab:** dedicated browser with trends per marker | TODO | MUST | Each lab value (cholesterol, K+, TSH, etc.) shows trend over time, reference ranges, last value highlighted |
| **Labs: connect to lab provider** (Quest, Labcorp) | TODO | NICE | Pull results automatically via integration |
| **Labs: upload results** — PDF upload + AI extraction | TODO | MUST | Upload lab report PDF → AI extracts values → populates trends. Same pattern as attachment analysis |
| **Labs: photo/scan upload** → AI reads lab results from image | TODO | NICE | Take photo of paper lab results → AI OCR + extraction → structured data |
| Apple Watch summary card (Resting HR, PVC Events, Steps) | OK | MUST | Already working |
| Blood Pressure Trend chart | OK | MUST | Already working |
| Heart Rate Trend chart | OK | MUST | Already working |
| **Add: Heart Rate Variability (HRV) chart** | TODO | MUST | Popular health metric, important for cardiac patients |
| **Add: Sleep information** (duration, quality, stages) | TODO | MUST | Critical health metric, ties to connected devices |
| **Weight Trend — change to bar chart** with average line + period delta | TODO | MUST | Currently line chart, should be bars + "−2.3kg in last 30 days" type summary |
| **All vitals: time range filter** (7d / 30d / 90d / 1y) | TODO | MUST | Sorting/filtering by time period |

### Cross-cutting: Context → Chat (MUST — applies to ALL My Health sections)

Every element across Health Profile, Connected Services, Vitals, Labs must be **selectable as context for the AI chat**. User can pick any data point (BP trend, lab result, medication, allergy, connected device data) and ask the AI about it in relation to their visit.

Example: Patient looks at BP trend → clicks "Ask about this" → Chat opens with context: "Looking at my blood pressure trend from the last 2 weeks and my last cardiology visit, is my current blood pressure good?"

This is the **core value loop**: visit data + health data + AI chat = personalized health understanding.

### Documents Tab (inside My Health)

| Element | Status | Category | Notes |
|---------|--------|----------|-------|
| Documents list — icons look poor | TODO | MUST | Generic/ugly icons, need proper file type icons |
| Documents list — status badges look mockup-ish | TODO | MUST | Need more polished design |
| **Each document = database entry** | TODO | MUST | Every document stored as DB record, queryable, not just file storage |
| **Each document usable as chat context** | TODO | MUST | User can select any document and ask AI about it in relation to their health |

---

## Cross-cutting: AI Chat Assistant on EVERY Page (CRITICAL MUST)

The AI chat assistant MUST be available on **every page and section** of the application, not just the Visit Detail screen. This is the core product differentiator.

| Requirement | Status | Category | Notes |
|-------------|--------|----------|-------|
| **AI Chat accessible from every page** | TODO | MUST | Visit Detail, My Health, Library, Doctor views — all need chat entry |
| **Context selection from any section** | TODO | MUST | User can select any data point as context: SOAP section, vital trend, lab result, document, medication, condition |
| **"Ask about this" button on every data element** | TODO | MUST | Consistent UX pattern across all screens — click → chat opens with that element as context |
| **Chat panel architecture** | TODO | MUST | Reusable side panel (desktop: right column, mobile: tab/bottom sheet) that works across all pages |

**This is the core value loop:** visit data + health data + documents + AI chat = personalized health understanding. The chat must be omnipresent.

---

## Screen 11: Medical Library

| Element | Status | Category | Notes |
|---------|--------|----------|-------|
| Your Conditions (auto-populated from visits) | OK | MUST | Working — shows PVCs |
| Your Medications (auto-populated) | OK | MUST | Working — Propranolol with "Drug info" |
| Clinical References (PubMed Verified) | OK | MUST | 8 guidelines, good quality |
| "Relevant for You" / "Ask AI" / "Search Databases" tabs | OK | MUST | |
| OpenEvidence Integration | COMING SOON | NICE | Banner present |
| **Add custom documents: URL, PDF upload** | TODO | MUST | Patient can add their own references, doctor's handouts, hospital PDFs |
| **Per-profile library** — each scenario/patient has own conditions + meds + refs | TODO | MUST | Tied to scenario picker — PVC patient gets cardiac refs, HF patient gets HF refs |
| Search Databases tab — currently shows only ICD-10 codes, no clinical value | WEAK | SKIP | Needs richer data sources, source management. Too much work for demo — Linear issue |

---

## Screen 12: Settings

**Philosophy:** Mock for demo. Show the structure exists, don't build real functionality.

| Element | Status | Category | Notes |
|---------|--------|----------|-------|
| **Audit Logs viewer** — list of system events | TODO | MUST | Mock UI showing audit trail (login, data access, AI queries). Backend endpoint already exists (`GET /audit/logs`) |
| **Document permissions** — who can see what | TODO | MUST | Mock UI: list of documents with toggles for sharing with doctor/family. Demonstrates privacy control concept |
| **System logs viewer** | TODO | NICE | Mock — show recent system activity |
| Profile settings (name, email, password) | TODO | NICE | Standard account settings, low priority |

---

## Bugs Found During Review

| # | Screen | Bug | Severity | Fix? |
|---|--------|-----|----------|------|
| 1 | Visit Detail | "Dr. Ciarka" — real PII in AI-generated visit note (Chief Complaint) | HIGH | MUST — remove/replace with fictional name before demo |
| 2 | Visit Detail | "Current medications" appear inside Reported Symptoms and Physical Examination sections — wrong SOAP classification | HIGH | MUST — ScribeProcessor puts meds in wrong sections |
| 3 | Visit Detail | Physical Exam section shows "Physical examination of reported symptoms" — nonsensical content, meds mixed in | HIGH | MUST — same root cause as #2 |
| 4 | Visit Detail | AI Analysis only appears after manual page refresh — should auto-update when done | HIGH | MUST — polling/SSE needed |

---

## Summary

### Status Counts
- **MUST items OK/working:** ~22 (Landing, Login buttons, SOAP sections, term highlighting, chat streaming, recording, processing, vitals charts, library conditions/meds/refs)
- **MUST items TODO:** ~45 (scenario picker, layout reorder, visit summary, chat two-column, quality gate, health profile expansion, labs tab, HRV/sleep, time filters, AI chat everywhere, ask-about-this buttons, documents DB, etc.)
- **MUST items BROKEN/BUG:** 5 (Try Demo auto-login, PII in notes, meds in wrong SOAP sections, AI Analysis no auto-refresh, term highlighter missing in AI Analysis)
- **NICE items:** ~15 (background gradient, signup link, text selection explain, connected services polish, ElevenLabs audio, smoother animations, photo scan upload)
- **SKIP items:** 1 (Search Databases ICD-10 only)

### Bugs Found: 4

| # | Screen | Bug | Severity | Fix? |
|---|--------|-----|----------|------|
| 1 | Visit Detail | "Dr. Ciarka" — real PII in AI-generated visit note (Chief Complaint) | HIGH | MUST — remove/replace with fictional name before demo |
| 2 | Visit Detail | "Current medications" appear inside Reported Symptoms and Physical Examination sections — wrong SOAP classification | HIGH | MUST — ScribeProcessor puts meds in wrong sections |
| 3 | Visit Detail | Physical Exam section shows "Physical examination of reported symptoms" — nonsensical content, meds mixed in | HIGH | MUST — same root cause as #2 |
| 4 | Visit Detail | AI Analysis only appears after manual page refresh — should auto-update when done | HIGH | MUST — polling/SSE needed |
| 5 | Doctor Dashboard | Patient search sends param but backend doesn't filter — search does nothing | LOW | MUST — add WHERE first_name/last_name LIKE in DoctorController::patients() |

### All Screens Reviewed
