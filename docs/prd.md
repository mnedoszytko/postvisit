# PostVisit.ai — Product Requirements Document

## 1. Vision & Problem

### Vision
PostVisit.ai is the bridge between what happens in the doctor's office and what happens after. It's an AI system that maintains the full context of a clinical visit and helps the patient after leaving — not only clarifying what was said, but actively helping with follow-up and maintaining high quality of care between visits.

The system translates expert medical knowledge into clear, accessible language through AI — giving every patient access to the understanding that was previously locked behind medical jargon, time pressure, and information overload.

### Problem

**Patient side:**
- Patient doesn't remember recommendations after leaving the office
- Doctor spoke too fast, didn't have enough time to explain everything
- Too much information delivered at once — cognitive overload
- Patient doesn't understand medical terminology
- No way to reference back — patient can't verify or revisit what the doctor said
- No way to gain informed, objective understanding of recommendations — patient can't contextualize what the doctor said against current clinical guidelines
- No support during recovery — patient calls the clinic repeatedly with questions about the same visit
- Patient has no single place that connects their visit data with their broader health record

**Doctor side:**
- Doctor repeats the same explanations — wastes time
- Too much information to convey in a short visit window
- No feedback loop — doctor doesn't know if patient understood or is following recommendations

### Key Differentiators

**Visit-anchored, not generic.** The system is NOT a general health chatbot. It is anchored in the context of a specific visit AND the patient's broader health context. All answers stem from:
- The visit itself (discharge notes, prescriptions, test results, transcription)
- The patient's health record (Apple Health, Google Health, uploaded records)
- Evidence-based clinical guidelines (ESC, AHA, etc.)

**Patient-initiated context ("Reverse Scribe").** A critical differentiator: the patient can initiate recording of the visit themselves. This "reverse scribe" approach means the patient owns the transcription of their visit, giving them optimal access to the full context of what was discussed. This is different from existing systems where the scribe is always on the doctor's side.

**Expert knowledge made accessible.** This is the core "Break the Barriers" proposition — AI translates the full depth of medical expertise (visit context + clinical guidelines + health record) into language and format that any patient can understand and act on.

### Hackathon Fit (3 tracks)

- **Build a Tool That Should Exist** — This tool doesn't exist today. There is no system that combines visit context, patient health records, and clinical guidelines to provide post-visit support — and where the patient can initiate the entire process.
- **Break the Barriers** — Expert medical knowledge is translated into accessible language through AI. Patient gets informed, objective understanding that previously required a second consultation or medical degree.
- **Amplify Human Judgment** — Doctor-in-the-loop: feedback and context flow back to the physician. The system never replaces clinical decisions — it helps patients understand and follow them.

### Context Sources (full picture)
1. **Visit data** — discharge notes, recommendations, prescriptions, test results (EKG, ECHO, labs)
2. **Visit transcription** — ambient scribing initiated by the patient ("reverse scribe")
3. **Patient health record** — Apple Health, Google Health, uploaded medical records
4. **Clinical guidelines** — ESC, AHA, and other evidence-based guidelines
5. **Doctor's scribe** (optional, future) — separate transcription from the doctor's side

## 2. Users & Personas

### Primary User: Patient

**Demographics:** Adults 18+, tech-savvy (this is future-of-healthcare technology — we design for the patient of the future).

**Core principle: Patient is the owner of their health data.** The patient decides what data enters the system, who sees it, and what happens with it. The architecture must reflect this — patient is the master of their record.

**What the patient does in the system:**
- Initiates visit recording (reverse scribe) — owns the transcription
- Views and explores their visit summary (recommendations, prescriptions, test results)
- Asks questions about the visit in natural language — gets answers grounded in visit context + clinical guidelines
- Connects external health data (Apple Health, Google Health, uploaded records)
- Reports post-visit follow-up data back to the doctor:
  - Blood pressure measurements (hypertension patients)
  - Blood glucose levels (diabetes patients)
  - Weight tracking (heart failure patients)
  - Symptom reports (e.g., chest pain → urgent escalation to doctor)
- Decides what to share with the doctor through the feedback channel

**Access model:** TBD — to be decided during architecture. Key principle: patient is the owner and initiator. Whether via account, magic link, or other mechanism — the patient controls the flow.

### Secondary User: Doctor

**Role:** The doctor is the clinical authority. The system amplifies the doctor's judgment, never replaces it.

**What the doctor does in the system:**
- Views post-visit follow-up data submitted by the patient (blood pressure, glucose, weight, symptoms)
- Receives alerts when patient reports concerning symptoms (chest pain, worsening condition)
- Sees whether the patient has engaged with recommendations (read, asked questions)
- Optionally: reviews what the AI explained to the patient (transparency / audit trail)

**Key design constraint:** Minimal effort required from the doctor. The system must not add administrative burden — the value is in the feedback loop that currently doesn't exist.

### Architecture Note: Where Patient and Doctor Paths Meet

The visit record is the meeting point. Both patient and doctor can contribute to and view the visit — from different angles:
- **Patient path:** records visit (reverse scribe) → receives AI-powered summary → asks questions → submits follow-up data
- **Doctor path:** reviews follow-up data → receives alerts → sees patient engagement
- **Shared object:** the visit record, enriched from both sides

This convergence must be reflected in the data model — the visit is not owned by one side. It's a shared clinical context that both actors contribute to and benefit from.

## 3. User Stories

### Patient Stories — Demo (must-have)

| # | Story | Notes |
|---|-------|-------|
| P1 | As a patient, I want to record my doctor's visit with my phone (reverse scribe) so that I have the full context of the conversation | Patient-initiated recording — core differentiator |
| P2 | As a patient, I want to see a detailed summary of my visit — diagnosis, history, physical exam, additional tests (EKG, ECHO), recommendations, prescriptions — with the ability to interact with each element | Not just a flat summary — each section is explorable. Patient can click into any element to understand it better |
| P3 | As a patient, I want to click on a medical term and get an explanation in the context of my specific visit | "Translate" button on any medical element — plain language, visit-specific |
| P4 | As a patient, I want to ask questions about my visit and get answers grounded in what the doctor said + current clinical guidelines | Free-form Q&A, anchored in visit context + evidence-based guidelines |
| P5 | As a patient, I want to know when my symptoms require urgent contact with a doctor (escalation) | System recognizes danger signals and directs patient to seek care |
| P6 | As a patient, I want to send feedback or a message to my doctor through the system | Feedback channel — patient can communicate back to the doctor (e.g., report symptoms, ask follow-up questions). This is the bridge. |

### Patient Stories — Post-Demo (roadmap)

| # | Story | Notes |
|---|-------|-------|
| P7 | As a patient, I want to connect Apple Health / Google Health so the system has my full health context | External health data integration |
| P8 | As a patient, I want to upload my own medical documents (PDFs, images, scans, lab results) so the system can include them in my record | Patient enriches their record with additional documentation — categorized and structured |
| P9 | As a patient, I want to send my doctor follow-up measurements (blood pressure, glucose, weight) through the system | Structured follow-up data for monitoring between visits |
| P10 | As a patient, I want to report new symptoms (e.g., chest pain) and have my doctor notified | Urgent symptom reporting with doctor notification |

### Doctor Stories — Demo (must-have)

| # | Story | Notes |
|---|-------|-------|
| D1 | As a doctor, I want to see that my patient has read the recommendations and what questions they asked | Patient engagement visibility |
| D2 | As a doctor, I want to see what the AI explained to my patient (audit trail) | Transparency — doctor knows exactly what the patient was told |
| D3 | As a doctor, I want to be notified when a patient wants to contact me and be able to respond | Feedback loop — doctor receives patient messages and can give return contact |

### Doctor Stories — Post-Demo (roadmap)

| # | Story | Notes |
|---|-------|-------|
| D4 | As a doctor, I want to receive an alert when a patient reports concerning symptoms | Urgent symptom escalation to doctor |
| D5 | As a doctor, I want to view follow-up data from my patient (blood pressure, glucose, weight) in a structured dashboard | Monitoring between visits |

### Priority Note
Patient is the priority. Doctor features are important for the "amplify human judgment" narrative and the feedback loop, but the core demo value is on the patient side.

## 4. Functional Requirements (Modules)

### Module 1: Core Health
**Foundation layer.** Patient's health record — the data backbone of the entire system.

Responsibilities:
- Store and manage patient health records
- Store visit records (the shared object between patient and doctor)
- Handle uploaded documents (PDFs, images, scans, lab results) — categorized and structured
- Integration points for Apple Health / Google Health (roadmap)
- Patient owns the data — access control starts here

Related stories: P7, P8 (roadmap)

---

### Module 2: Companion Scribe
**Patient-initiated visit recording and transcription.**

Responsibilities:
- Patient records the visit on their phone (ambient recording)
- Speech-to-text pipeline produces a transcript
- Transcript becomes a primary context source for PostVisit module
- Patient owns the recording and transcript

Open decision: STT engine (Whisper / Deepgram / other) — to be decided during architecture.

Related stories: P1

---

### Module 3: PostVisit
**The main AI engine.** Takes all visit data and produces an interactive, structured experience for the patient. This is where Opus 4.6 does the heavy lifting.

Responsibilities:
- Parse raw visit data (transcript + discharge notes + records) into structured sections:
  - Diagnosis
  - History / interview
  - Physical examination
  - Additional tests (EKG, ECHO, labs — specialty-dependent)
  - Recommendations
  - Prescriptions
  - Next steps
- Each section is interactive — patient can click/expand/explore
- "Translate" button on any medical element → plain language explanation in the context of this specific visit (not generic)
- Free-form Q&A chat anchored in visit context — under the hood pulls from Reference and Meds modules
- Escalation (cross-cutting): recognizes dangerous symptoms in patient questions → red alert directing to seek care immediately

Related stories: P2, P3, P4, P5

---

### Module 4: Reference
**Clinical knowledge layer.** Evidence-based sources that enrich the AI's answers beyond visit-specific data.

Responsibilities:
- Connect to and serve clinical guidelines (ESC, AHA, specialty-specific)
- Provide evidence-based context for PostVisit answers
- Enable validation — patient can understand how recommendations align with current guidelines
- Search and retrieval interface for knowledge sources

This is NOT visit-specific data — it's the reference knowledge that gives depth and objectivity to PostVisit's answers.

Related stories: P4 (enriches Q&A answers)

---

### Module 5: Meds
**Medication module.** Dedicated to understanding prescriptions — specific enough to warrant its own module.

Responsibilities:
- Explain what was prescribed and why (in context of this visit)
- Dosing information and schedule
- Drug interactions — flag potential issues
- Side effects — what to watch for
- Changes from previous medications (new / changed / continued)
- Connect to drug databases for authoritative information

Related stories: P2 (medications section), P4 (medication questions)

---

### Module 6: Feedback
**The bridge between patient and doctor.** Bidirectional communication channel.

Responsibilities:
- Patient sends messages, follow-up data, symptom reports to doctor
- Doctor receives notifications about patient messages
- Doctor can respond / give return contact
- Structured follow-up data: blood pressure, glucose, weight (roadmap)
- Urgent symptom reporting with escalation to doctor (roadmap)

Related stories: P6, P9, P10 (roadmap), D3

---

### Module 7: Doctor
**Doctor's dashboard.** Minimal-effort interface showing what matters.

Responsibilities:
- View patient engagement: did they read recommendations, what questions did they ask
- Audit trail: what did the AI explain to the patient (full transparency)
- Notification center: patient messages, urgent symptoms
- Return contact capability
- Overview of follow-up data from patients (roadmap)

Related stories: D1, D2, D3, D4, D5

---

### Module 8: Audit & Security
**Compliance and logging layer.** Cross-cutting concern across all modules.

Responsibilities:
- Log all data access (who accessed what, when)
- Audit trail of AI interactions (what was asked, what was answered)
- Access control: patient vs doctor permissions
- HIPAA/GDPR compliance layer (minimum for demo, architecture-ready for production)
- Encryption in transit and at rest (infrastructure level)

Related stories: D2, compliance requirements from Section 10

---

### Module 9: Demo Engine
**Simulated visit experience for demo and onboarding.** Allows anyone to experience the full PostVisit flow without a real doctor's visit.

Responsibilities:
- Simulate a complete visit: patient "enters", the recording starts, doctor-patient conversation plays out
- Pre-recorded audio/transcript of a simulated clinical visit (cardiology scenario)
- After simulation completes → system generates a full PostVisit experience with mock data
- Mock data layer: realistic discharge notes, prescriptions, test results (EKG, ECHO) seeded for the demo scenario
- Provides the "wow moment" for judges — click one button, experience the entire flow
- Reusable for onboarding: future patients could use demo mode to understand the system before their first real visit
- **Doctor side of demo**: simulated doctor dashboard showing mock patient engagement, mock follow-up data, mock notifications — so judges can see both sides of the bridge

Related stories: Demo scenario (Section 9)

---

### Cross-Cutting Concerns (not separate modules)
These live inside other modules:
- **Escalation** — PostVisit + Meds + Feedback detect danger signals and escalate. Not a module, but a behavior pattern across the system.
- **Q&A Chat** — UX layer of PostVisit that under the hood queries Reference and Meds for enriched answers.
- **Medical Explain / Translate** — Feature within PostVisit, not a standalone module.

## 5. Screens & UI Flow

### Design Direction
- Color palette: green-based, medical but modern
- Typography: large, slightly cursive/modern fonts — elegant, readable
- Feel: premium healthcare, not clinical/sterile — approachable but trustworthy
- Full visual mockups to be designed iteratively with Claude (frontend-design plugin)

### Key Screen: Visit View (patient's main screen)

This is the central screen — the hub of the entire patient experience.

**Header:**
- Visit: Dr. [Name], [Date], [Specialty e.g. Cardiology]

**Sections (scrollable, each interactive):**
1. Reason for visit
2. Symptoms
3. History / Interview
4. Comorbidities
5. Current medications
6. Physical examination
7. Additional tests (specialty-dependent):
   - Cardiology: ECHO, EKG, stress test, Holter
   - Other specialties: relevant tests
8. Conclusions
9. Additional documents (if any)
10. Recommendations
11. Next steps

**Interaction pattern:** When patient clicks any section → a chat panel slides in from the right side, partially overlaying the visit view. This is the AI assistant (powered by Opus 4.6, connected to full visit context + Reference + Meds). Patient can ask questions about that specific element or anything from the visit.

### Key Screen: Chat Panel (S6)

The chat panel is more than a simple text chat. It's a rich interaction surface leveraging Opus 4.6's 1M token context window.

**Capabilities:**
- **Text input** — standard chat, ask anything about the visit
- **Voice input** — patient can speak instead of typing (important for accessibility and comfort)
- **Attachments** — patient can add files, images, documents to the conversation (+ button). Adds to the context for Opus to reason about.
- **Additional context** — the chat can be enriched beyond the visit. The primary context is the visit, but patient can feed in more data for Opus to discuss.

**Design:** Similar in spirit to Claude or ChatGPT — a chat interface that feels powerful and extensible. Input bar at the bottom with: text field, voice button, attachment (+) button. Conversation history above.

**Key point:** Opus 4.6 has a massive context window — the chat should let the patient take full advantage of that. The visit is the anchor, but the conversation can grow.

### Key Screen: Meds Detail (S7)

**Hybrid approach:** Quick summary inside Visit View (medications section) + dedicated full Meds screen for the deep dive. This module is too complex for just a section expansion.

**In Visit View (summary):**
- List of medications: name, dose, frequency, new/changed/continued badge
- Click any medication → opens full Meds screen

**Full Meds Screen — per medication card:**
- Drug name, dose, frequency, route
- Why it was prescribed (linked to this visit's diagnosis)
- Duration — how long, until when
- Prescription status — does the patient need a new prescription, how many refills left
- Interactions — flagged if any interaction with other medications the patient takes
- Side effects — what to watch for
- Reminders — dosing schedule / notification setup (roadmap)

**Full Meds Screen — overview level:**
- All current medications in one view
- Interaction matrix — visual flag if any drugs interact with each other
- Timeline — what changed at this visit vs. before

Data source: drug database (to be selected — open decision) + visit context from Opus.

### Key Screen: Feedback / Contact Doctor (S8)

**Separate, dedicated screen.** Not buried inside the chat — this is direct communication with the doctor.

**Elements:**
- Doctor card: photo/avatar + name + specialty (selectable if patient has multiple doctors)
- Message thread — simple messaging interface to write to the doctor
- Option to book a follow-up visit (appointment booking)
- Attach files if needed (lab results, photos, etc.)

**Design:** Clean, personal feel. The doctor icon/photo should be prominent — patient feels like they're reaching out to a real person, not a system.

### Key Screen: Doctor Dashboard (S9)

**Comfortable, spacious interface.** Not cramped — this is the doctor's workspace. Must feel professional and efficient.

**Elements:**
- Patient list with search — clean, well-designed list of patients with photos/avatars
- Notification badges — visible icons indicating:
  - New messages from patients
  - Upcoming/recent visits
  - Urgent symptom reports (if applicable)
- Quick access to communication — doctor can respond to patients directly from the dashboard
- Each patient row shows: name, last visit date, notification status (unread messages, new activity)

**Design:** Spacious, no clutter. Large enough elements that it's comfortable to use. Think medical professional tool — not a dense admin panel, but a clean dashboard that respects the doctor's time.

### Key Screen: Doctor — Patient Detail (S10)

Doctor clicks a patient from the dashboard and sees the full picture.

**Elements:**
- Patient profile: photo, name, age, conditions, current medications
- Visit history: list of all visits — doctor can click into any visit and see what happened (same visit data the patient sees, from the doctor's perspective)
- AI audit trail: what the AI explained to the patient, what questions the patient asked
- Patient engagement: did they read recommendations, how active are they
- Messages from patient: thread of communication
- Response: doctor can reply directly

### Key Screen: Demo Mode (S11)

A guided flow through all key features — respects the user's time while showing everything works.

**Flow:**
1. User clicks "Try Demo" on landing
2. Option A: **Try voice** — shows the Companion Scribe working, records a short interaction to demonstrate voice capability
3. Option B: **Skip to visit** — "Simulate a visit" button. Instantly loads a pre-built visit with full mock data (cardiology scenario). No waiting, no recording needed.
4. From the mock visit → user can freely explore: Visit View, click sections, open chat, check Meds, try contacting the doctor
5. Switch to doctor view — see the other side of the bridge with mock dashboard

**Key principle:** Every step has a shortcut. User can click through the full flow or skip ahead at any point. Demo never blocks or forces waiting. It's a showcase, not an obstacle course.

### Screen Inventory (to be designed one by one)

| # | Screen | Status | Notes |
|---|--------|--------|-------|
| S1 | Landing / Entry | TBD | "Try Demo" + login |
| S2 | Patient Profile / Health Record | Described below | Photo, age, DOB, conditions, meds, documents, visits |
| S3 | Companion Scribe (recording) | Described below | Consent → Record → Stop → add files |
| S4 | Processing / Loading | Described below | Particle animation while Opus works |
| S5 | **Visit View** | Described above | The main hub — priority screen |
| S6 | Chat Panel (slide-in) | Described below | AI assistant — text, voice, attachments |
| S7 | Meds Detail | Described below | Hybrid — summary in Visit View + full Meds screen |
| S8 | Feedback / Contact Doctor | Described below | Doctor icon, messaging, book visit |
| S9 | Doctor Dashboard | Described below | Patient list, search, notifications |
| S10 | Doctor — Patient Detail | Described below | Visits, engagement, audit, messages |
| S11 | Demo Mode | Described below | Skip-friendly simulated visit |

### Key Screen: Landing Page (S1)

Beautiful, simple, animated. Must hook in 3 seconds.

**Core message:** "The missing bridge between doctor and patient." / "After the visit, the patient comes first."

**Elements:**
- PostVisit.ai logo + tagline
- Subtle animation (bridge metaphor? to be explored visually)
- Two CTAs: "Try Demo" + "Sign Up" / "Log In"
- Clean, premium feel — no clutter, no walls of text
- Possibly a brief visual showing the flow (visit → AI → patient understanding)

Design to be iterated visually — this screen needs to "wow" judges in the first 3 seconds.

---

### Key Screen: Patient Profile / Health Record (S2)

Entry point for the patient after login. Their personal health hub.

**Content:**
- Photo, name, age, date of birth
- Chronic conditions / comorbidities
- Current medications
- Uploaded documents (PDFs, scans, lab results)
- Visit history (list of past visits → click to open Visit View)
- Connected sources (Apple Health, Google Health — roadmap)

This is the "home base" — patient sees their full record here and navigates to specific visits.

---

### Key Screen: Processing (S4)

Beautiful, modern animation while Opus processes the visit data.

**Visual concept:** Particle system (Three.js or similar) — points/particles in the background that react and move. During recording they respond to voice input. During processing they animate organically — conveying that the system is "thinking". Modern, premium, mesmerizing.

**Content:** Simple status message ("Analyzing your visit..." or similar). No step-by-step progress — just the animation and a message. Clean and elegant.

**Transitions:** This screen appears both during recording (particles react to sound) and after recording while Opus generates the Visit View. Smooth transition into the Visit View when ready.

---

### Key Screen: Companion Scribe — Recording (S3)

This screen is tied to a specific visit — recording happens within the context of a visit record.

**Flow:**
1. **Consent step first.** Before recording starts, both provider and patient must give consent. Clear message: "Both the provider and the patient consent to this recording." Confirm button.
2. **Recording.** Big record button, big animation showing it's listening (waveform / pulse). Simple and obvious. Timer showing duration.
3. **Stop.** Patient taps to stop recording.
4. **After recording:** option to attach additional files to this visit (documents, PDFs, images, lab results). The recording + attached files all belong to this specific visit context.

**Design:** Simple, clean, no clutter. The consent step must be prominent and unambiguous. The recording animation must be clear — patient knows the system is listening.

---

Screens will be designed iteratively — one by one, with visual mockups via Claude frontend-design plugin.

## 6. AI Architecture

### Context Assembly Strategy

Opus 4.6 has a 1M token context window. We leverage this by assembling a rich, layered context — but we must be smart about it.

**CRITICAL: No context duplication.** Static context (visit data, patient record, clinical guidelines) is loaded ONCE and stays fixed. Only the conversation history grows per turn. We never re-inject the full visit data + patient record + guidelines with every message exchange. Structure:

```
┌─────────────────────────────────────┐
│  System Prompt (from prompts/)      │  ← loaded once per session
├─────────────────────────────────────┤
│  Visit Data (static per session)    │  ← transcript, discharge, tests
├─────────────────────────────────────┤
│  Patient Record (static)            │  ← conditions, meds, history
├─────────────────────────────────────┤
│  Clinical Guidelines (static)       │  ← ESC, AHA, relevant guidelines
├─────────────────────────────────────┤
│  Meds Data (static)                 │  ← drug info, interactions
├─────────────────────────────────────┤
│  Conversation History (grows)       │  ← only this grows per turn
├─────────────────────────────────────┤
│  User Message                       │  ← current question
└─────────────────────────────────────┘
```

Static layers are assembled at session start. Per-turn, only conversation history + new user message are appended.

### Prompt Architecture — Multiple AI Subsystems

This is NOT a single-prompt system. Each AI-driven function has its own dedicated, parameterized prompt. All prompts are stored in `prompts/` as versioned files — never hardcoded in controllers.

**Identified AI subsystems (each with its own prompt):**

| # | Subsystem | What it does | Prompt file |
|---|-----------|-------------|-------------|
| 1 | **Scribe Processor** | Transforms raw audio transcript into clean, structured text | `prompts/scribe-processor.md` |
| 2 | **Visit Structurer** | Parses transcript + documents into structured visit sections (diagnosis, history, exam, tests, meds, recommendations) | `prompts/visit-structurer.md` |
| 3 | **Document Analyzer** | Analyzes uploaded documents (PDFs, lab results, scans) and extracts relevant medical data | `prompts/document-analyzer.md` |
| 4 | **Q&A Assistant** | The main chat — answers patient questions grounded in visit context + guidelines | `prompts/qa-assistant.md` |
| 5 | **Medical Explainer** | Translates medical terms/sections into plain language in the context of this visit | `prompts/medical-explainer.md` |
| 6 | **Meds Analyzer** | Analyzes prescriptions, interactions, dosing, side effects | `prompts/meds-analyzer.md` |
| 7 | **Escalation Detector** | Identifies dangerous symptoms in patient input and triggers alerts | `prompts/escalation-detector.md` |
| 8 | **Visit Summarizer** | Generates the patient-facing visit summary from structured data | `prompts/visit-summarizer.md` |

**Key principle: every AI-touching element must be parameterized.** Each subsystem has its own prompt, its own input/output contract, and can be iterated independently. This also makes it easy to test individual subsystems with Sonnet while using Opus in production.

**Detailed prompt engineering** for each subsystem — to be done during implementation when we can iterate directly with the model. Logged as open task.

### System Prompt (Q&A Assistant — main chat)

The primary chat prompt defines how the AI behaves in conversation. High-level behavioral rules:
- You are a post-visit assistant, not a doctor
- You answer ONLY based on the visit context + clinical guidelines
- You never diagnose, never prescribe, never issue new recommendations
- When you detect dangerous symptoms → escalate immediately (direct patient to seek care)
- Language: simple, clear, accessible — translate medical jargon
- Always ground answers in the specific visit, not general knowledge

### AI Pipeline

**1. Companion Scribe → Transcript**
- Audio recording → STT engine → raw transcript
- STT engine: TBD (open decision — Whisper / Deepgram / other)

**2. Transcript → Structured Visit**
- Opus 4.6 receives: raw transcript + any uploaded documents (discharge notes, lab results)
- Opus parses and structures into visit sections (diagnosis, history, exam, tests, recommendations, meds, next steps)
- Output is stored as structured data in the visit record

**3. Interactive Q&A (Chat Panel)**
- Full context loaded once at session start (system prompt + visit + record + guidelines + meds)
- Patient asks questions → Opus answers grounded in context
- Conversation history appended per turn
- Escalation logic embedded in system prompt behavior

**4. Medical Explain / Translate**
- Patient clicks element → Opus receives: "Explain [this element] in simple language, in the context of this patient's visit"
- Same context session — no re-loading

### Agentic Architecture

This is a modern system built on agentic paradigms — not just traditional API request/response cycles.

**Core idea:** AI subsystems interact with each other as agents. The system exposes instructions for other agents to interact with it. Most internal operations work through agentic discussion, not just plain API calls.

**Examples:**
- Scribe Processor doesn't just "call" Visit Structurer via API — it hands off context to an agent that reasons about how to structure the visit
- Document Analyzer can ask the Meds Analyzer for drug-specific context when parsing a lab result that mentions medication levels
- Escalation Detector runs as a persistent agent concern, monitoring all interactions

**External API:** The system also exposes a structured API (GraphQL or similar) for extensibility and third-party integrations. But the internal brain is agentic.

This is a key differentiator for the hackathon — demonstrates creative use of Opus 4.6 beyond simple prompt-response patterns.

### Open Decisions (to be resolved during implementation)

| Decision | Options | Status |
|----------|---------|--------|
| STT engine | Whisper / Deepgram / AssemblyAI / other | TBD |
| Voice output (chat) | Anthropic TTS / browser TTS / external | TBD |
| Drug database source | OpenFDA / DrugBank / other (must be open source) | TBD |
| Guidelines format | Full text in context / RAG / chunked | TBD |
| External API | GraphQL / REST / hybrid | TBD |
| Detailed system prompts | To be engineered during implementation | Open task |

## 7. Data Model

**STATUS: COMPLETE.** Full FHIR R4 aligned data model in `docs/data-model.md`.

### Summary

- **17 tables** mapped to FHIR R4 resources (Patient, Encounter, Observation, Condition, MedicationRequest, etc.)
- **Coding systems:** ICD-10-CM, ICD-11, SNOMED CT, LOINC, RxNorm, ATC, CPT
- **PostgreSQL** — chosen for jsonb (specialty_data, extracted_entities), tsvector (full-text search), native UUID, partitioning (audit_logs)
- **Extensibility:** `specialty_data` jsonb field on observations handles any specialty (cardiology ECHO/EKG, endocrinology, etc.) without schema changes
- **HIPAA:** audit_logs with phi_accessed tracking, soft deletes, encryption markers
- **GDPR:** consents table + consent fields on patients

### Key Design Decisions

1. **`diagnostic_reports` removed** — observations + documents + visit_notes cover this. No duplication.
2. **`medications` as RxNorm cache** — propranolol seeded for demo reliability. All other drugs fetched from RxNorm API on-demand and cached locally. Search always works.
3. **`roles` simplified** — enum on `users` table for demo (patient|doctor|admin). Full RBAC table is roadmap.
4. **`consents` table** — excluded from demo. Consent fields on `patients` are sufficient.
5. **`notifications` table** — needed but missing from data-model.md. To be added during implementation.

### Relationships

```
patients ──1:n──► visits ──1:n──► observations
    │                 │               │
    │                 ├──1:n──► conditions
    │                 ├──1:n──► prescriptions ──n:1──► medications
    │                 ├──1:n──► documents
    │                 ├──1:1──► visit_notes
    │                 ├──1:1──► transcripts
    │                 └──1:n──► chat_sessions ──1:n──► chat_messages
    │
    ├──1:n──► consents
    └──1:1──► users

practitioners ──1:n──► visits
         │
         ├──1:n──► prescriptions
         ├──1:n──► visit_notes
         └──n:1──► organizations

medications ──1:n──► medication_interactions (self-referencing)

users ──1:n──► audit_logs
```

Full field definitions, coding system details, jsonb examples, and demo scope: see `docs/data-model.md`.

## 8. API Design

### Design Philosophy

This is NOT a standalone island. PostVisit.ai is designed as a scalable product that will be part of a larger healthcare ecosystem. The API must reflect this from day one:

1. **Interoperability-first.** FHIR R4 data model means we can expose native FHIR endpoints when needed. Other healthcare systems can integrate.
2. **Agent-friendly.** The system operates on agentic paradigms — external AI agents must be able to interact with PostVisit through a documented, discoverable API.
3. **Ecosystem-ready.** GraphQL layer (roadmap) for self-documenting, introspectable API that agents and third-party systems can discover and navigate programmatically.

### Conventions

- **Prefix:** `/api/v1/`
- **Format:** JSON, FHIR-aligned naming where applicable
- **Auth:** Laravel Sanctum (token-based, SPA-friendly)
- **Responses:** `{ data: {...}, meta: {...} }` — Laravel API Resource standard
- **Errors:** `{ error: { code, message, details } }`
- **Pagination:** cursor-based (Laravel built-in)
- **Rate limiting:** Laravel throttle middleware

### API Surface by Module

#### Module 1: Core Health

```
GET    /patients/{id}                         → patient profile + conditions + meds
PATCH  /patients/{id}                         → update patient data
GET    /patients/{id}/visits                  → visit history (paginated)
GET    /patients/{id}/conditions              → conditions list
POST   /patients/{id}/conditions              → add condition
GET    /patients/{id}/health-record           → aggregated health record
POST   /patients/{id}/documents               → upload document
GET    /patients/{id}/documents               → list documents (filterable by type)
GET    /documents/{id}                        → document detail + download URL
```

#### Module 2: Companion Scribe

```
POST   /visits                                → create new visit (starts recording context)
POST   /visits/{id}/transcript                → upload audio file or raw transcript text
GET    /visits/{id}/transcript                → get transcript + processing status
POST   /visits/{id}/transcript/process        → trigger AI processing pipeline
GET    /visits/{id}/transcript/status         → processing status (pending|processing|completed|failed)
```

#### Module 3: PostVisit (AI engine)

```
GET    /visits/{id}                           → full structured visit (all sections)
GET    /visits/{id}/summary                   → patient-friendly summary
POST   /visits/{id}/explain                   → explain medical term/section in visit context
         body: { element: "Paroxysmal Ventricular Contractions", section: "diagnosis" }
         response: { explanation: "...", sources: [...] }

POST   /visits/{id}/chat                      → send message, receive AI response
         body: { message: "What causes PVCs?" }
         response: SSE stream of AI response tokens

GET    /visits/{id}/chat/history              → full chat history for this visit
```

#### Module 4: Reference (internal — not directly exposed)

Reference module is consumed internally by PostVisit AI subsystems. No direct patient-facing endpoints. Evidence is surfaced through chat responses and explanations with source citations.

#### Module 5: Meds

```
GET    /medications/search?q={query}          → RxNorm API proxy — search drugs by name
         response: { data: [{ rxnorm_code, generic_name, brand_names, form, strength }] }

GET    /medications/{rxnorm_code}             → drug detail (from local cache or RxNorm fetch)
GET    /medications/{rxnorm_code}/interactions → known drug interactions
GET    /visits/{id}/prescriptions             → prescriptions for this visit
GET    /patients/{id}/prescriptions           → all active prescriptions for patient
GET    /patients/{id}/prescriptions/interactions → interaction check across all patient meds
```

#### Module 6: Feedback

```
POST   /visits/{id}/messages                  → patient sends message to doctor
         body: { content: "...", attachments: [...] }
GET    /visits/{id}/messages                  → message thread (paginated)
PATCH  /messages/{id}/read                    → mark message as read
```

#### Module 7: Doctor

```
GET    /doctor/dashboard                      → overview: patient count, unread messages, alerts
GET    /doctor/patients                       → patient list (searchable, sortable, paginated)
GET    /doctor/patients/{id}                  → patient detail: profile, visits, engagement
GET    /doctor/patients/{id}/visits           → patient's visit history
GET    /doctor/patients/{id}/engagement       → what patient read, asked, clicked
GET    /doctor/patients/{id}/chat-audit       → what AI told the patient (full audit)
GET    /doctor/notifications                  → unread messages, alerts, flags
POST   /doctor/messages/{id}/reply            → doctor responds to patient message
```

#### Module 8: Audit & Security

```
GET    /audit/logs                            → filtered audit trail (admin/doctor only)
         params: ?resource_type=&user_id=&from=&to=&phi_accessed=
```

#### Module 9: Demo Engine

```
POST   /demo/start                            → create demo session with seeded visit + mock data
GET    /demo/status                           → current demo session state
POST   /demo/reset                            → reset demo to initial state
POST   /demo/simulate-alert                   → trigger mock 2AM alert for doctor dashboard
```

#### Auth

```
POST   /auth/register                         → create account (patient or doctor)
POST   /auth/login                            → get Sanctum token
POST   /auth/logout                           → revoke token
GET    /auth/user                             → current user + role + linked patient/practitioner
```

### Key Architecture Decisions

1. **Chat = stateless per request.** Client sends only the message. Server assembles full context (visit + record + guidelines + history) and calls Opus. Client never manages AI context.

2. **SSE for AI responses.** Endpoints that trigger AI (`/chat`, `/explain`) return Server-Sent Events — response streamed token by token. Laravel supports this natively. Better UX than waiting for full response.

3. **RxNorm proxy with local cache.** `/medications/search` hits RxNorm API and caches results in `medications` table. Frontend never calls RxNorm directly. Propranolol pre-seeded for demo reliability.

4. **FHIR export endpoints (roadmap).** Architecture supports future FHIR-native endpoints: `/patients/{id}/fhir/Patient`, `/visits/{id}/fhir/Encounter`. Not on demo, but data model is ready.

5. **GraphQL layer (roadmap).** Self-documenting API for agent-to-agent communication and third-party integrations. REST for demo, GraphQL for production extensibility.

6. **Webhook support (roadmap).** External systems can subscribe to events (visit completed, alert triggered, message sent). Enables integration into broader healthcare ecosystem.

### Interoperability Roadmap

| Phase | Capability | Standard |
|-------|-----------|----------|
| Demo | REST API + Sanctum auth | Laravel conventions |
| v1.0 | FHIR R4 export endpoints | HL7 FHIR R4 |
| v1.0 | GraphQL layer | GraphQL spec |
| v2.0 | SMART on FHIR app launch | SMART App Launch Framework |
| v2.0 | CDS Hooks service | HL7 CDS Hooks |
| v2.0 | Webhook subscriptions | Custom + FHIR Subscriptions |

## 9. Demo Scenario

### Video Script
Full script: `docs/video-script-v4.docx` (v4 draft, not final)
Feature requirements extracted: `docs/video-features-required.md`

**Tone:** 40% humor / 60% serious (changed from v4's 60/40 — more gravity, less comedy)
**Duration:** 3 minutes
**Language:** English

### Structure (6 scenes)

| Scene | Timing | What happens | Key beat |
|-------|--------|-------------|----------|
| 1. Intro — Who Am I? | 0:00–0:40 | Nedo as cardiologist + coder. PreVisit.ai press montage. "What happens AFTER the visit." | Hook + credibility |
| 2. The Patient Experience | 0:40–1:35 | Nedo becomes the patient. Cardiology tests montage. Doctor delivers diagnosis (PVCs). Patient walks out confused. | **Serious beat #1:** "Millions walk out confused" |
| 3. The Phone Was Listening | 1:35–2:10 | Phone recorded the visit (reverse scribe). PostVisit notification. Visit summary. Tap-to-explain. Q&A about propranolol. | **Serious beat #2:** "Hope and pray" — taking meds you don't understand |
| 4. Product Deep Dive | 2:10–2:30 | Screen recording: wearable integration, lab upload, follow-up timeline. | Features showcase |
| 5. The Loop Back to Doctor | 2:30–2:50 | Doctor dashboard. Patient data flows back. 2AM chest pain story → cath lab → 95% LAD stenosis. | **Serious beat #3:** "That's not a feature. That's a life." |
| 6. The Bridge — Closing | 2:50–3:00 | Golden Gate Bridge. 80% stat. Bridge metaphor. "The patient comes first." | **Serious beat #4:** Global scale, emotional close |

### Features That MUST Work for the Video

**Must-have (no demo without these):**
1. Visit summary screen — diagnosis + meds + next steps in plain language
2. Tap-to-explain — click "Paroxysmal Ventricular Contractions" → chat opens with explanation
3. Q&A chat — minimum 2 interactions: "What causes this?" + "What is propranolol?"
4. Doctor dashboard — patient insights + questions feed
5. Notification — "Your visit summary is ready" (can be mockup)

**Should-have (strengthens demo):**
6. Apple Watch / wearable integration (heart rhythm, PVC detection)
7. Lab results upload + AI analysis (cholesterol, potassium, thyroid)
8. Follow-up timeline (BP check 2 weeks, echo 3 months)
9. 2AM alert scenario on doctor dashboard

### Demo Data Required

| Data | Source | Format |
|------|--------|--------|
| Visit transcript (cardiologist + patient) | Written by Nedo (doctor) | Text |
| Discharge notes / visit summary | Written by Nedo | Text |
| Scenario: PVCs, propranolol 40mg 2x/day | seed.md | — |
| Apple Watch mock (HR, PVC events) | Generated mock | JSON |
| Lab results mock (cholesterol, K+, TSH) | Generated mock | JSON |
| Doctor dashboard mock (patient list, alerts) | Generated mock | JSON |

### Ambient Scribing in Video
Not screen-recorded — implied. Phone on doctor's desk during visit, 2-second flashback. Overlay: "With mutual consent of doctor and patient." But the backend MUST have an endpoint that accepts transcript and generates visit summary.

### Tagline
**"The bridge between your visit and your health."**

## 10. Compliance & Safety

### Regulatory Context

PostVisit.ai operates in healthcare — the most regulated industry. Two frameworks matter:

- **HIPAA** (US) — Protected Health Information (PHI) handling, audit trails, access control, encryption
- **GDPR** (EU) — patient consent, right to erasure, data portability, purpose limitation

For demo: we demonstrate **awareness and architecture readiness**, not full certification. Full HIPAA/GDPR compliance requires legal review, BAA agreements, and infrastructure hardening — that's production.

### HIPAA — What We Implement

**On demo:**

| Requirement | Implementation | Status |
|-------------|---------------|--------|
| Audit trail | `audit_logs` table — every data access logged with user, action, resource, timestamp, PHI flag | Demo |
| Access control | Role-based: patient sees own data, doctor sees assigned patients only | Demo |
| PHI encryption at rest | `ssn_encrypted` (AES-256), `ip_address` encrypted in audit logs | Demo |
| Encryption in transit | TLS (Let's Encrypt) on all endpoints | Demo |
| Minimum necessary | API returns only data relevant to the requesting user's role | Demo |
| Session management | Sanctum tokens with expiration | Demo |

**Roadmap (not demo):**

| Requirement | Notes |
|-------------|-------|
| BAA with Anthropic | Required for production PHI processing. Anthropic offers BAA for Claude healthcare. |
| BAA with hosting provider | Hetzner or HIPAA-compliant cloud (AWS/GCP) |
| Breach notification | 60-day notification policy |
| Data retention policy | Configurable per organization |
| Disaster recovery | Backups, failover |

### GDPR — What We Implement

**On demo:**

| Requirement | Implementation |
|-------------|---------------|
| Consent tracking | `patients.consent_given`, `consent_date`, `data_sharing_consent` |
| Right to erasure | `patients.right_to_erasure_requested` flag + soft deletes across all tables |
| Purpose limitation | AI uses data only in context of the visit it belongs to |
| Data minimization | System collects only what's needed for clinical context |

**Roadmap:**
- Full `consents` table with versioning, multi-type consent, expiration
- Data export (FHIR-native patient data export)
- Cookie consent (web frontend)
- DPO contact information

### AI Safety & Guardrails

**Behavioral guardrails (embedded in system prompts):**

1. **Never diagnose.** AI explains what the doctor said, never issues new diagnoses.
2. **Never prescribe.** AI explains prescribed medications, never suggests new ones.
3. **Never contradict the doctor.** AI contextualizes recommendations with guidelines, never overrides clinical decisions.
4. **Escalation protocol.** When patient describes dangerous symptoms (chest pain, severe bleeding, suicidal ideation) → immediate redirect to emergency care. No AI discussion of acute emergencies.
5. **Source grounding.** Every answer must be traceable to: visit data, clinical guidelines, or drug database. No hallucinated medical advice.
6. **Uncertainty disclosure.** When AI cannot answer confidently → "Please discuss this with your doctor" rather than guessing.

**Technical guardrails:**

| Guardrail | How |
|-----------|-----|
| Context boundary | AI only accesses data from the patient's own visits and record — never cross-patient |
| Token audit | Every AI interaction logged: model used, tokens consumed, prompt hash |
| Response review | Doctor can audit every AI response via dashboard (D2 story) |
| Rate limiting | Chat requests throttled to prevent abuse |
| Input sanitization | Patient messages sanitized before AI processing |

### Medical Disclaimer

Prominent on every screen, in README, in SECURITY.md:

> **PostVisit.ai is a patient education and communication tool. It is NOT a medical device, does NOT provide medical advice, and does NOT replace professional medical judgment. Always consult your healthcare provider for medical decisions.**

### Demo Data Policy

- **No real patient data.** All demo data is fictional, written by a physician for realism.
- **No real PHI.** Mock patients, mock visits, mock lab results.
- **Disclaimer in README:** "All patient data in this demo is fictional. No real patient information is used or stored."

### Security Architecture

```
┌─────────────────────────────────────────────┐
│  Frontend (Vue SPA)                          │
│  • No PHI stored client-side                 │
│  • Sanctum token in httpOnly cookie          │
│  • TLS only                                  │
└──────────────┬──────────────────────────────┘
               │ HTTPS
┌──────────────▼──────────────────────────────┐
│  Backend (Laravel API)                       │
│  • Sanctum auth middleware                   │
│  • Role-based access control                 │
│  • Input validation + sanitization           │
│  • Audit logging on every PHI access         │
│  • Rate limiting                             │
└──────────────┬──────────────────────────────┘
               │ Encrypted connection
┌──────────────▼──────────────────────────────┐
│  PostgreSQL                                  │
│  • Sensitive fields encrypted (AES-256)      │
│  • Soft deletes (GDPR erasure)               │
│  • Audit logs immutable (append-only)        │
└──────────────────────────────────────────────┘
               │
┌──────────────▼──────────────────────────────┐
│  Anthropic API (Claude Opus 4.6)             │
│  • Zero data retention (API policy)          │
│  • BAA available (production)                │
│  • Context assembled server-side only        │
└──────────────────────────────────────────────┘
```

## 11. Out of Scope

Explicit boundaries — what is NOT in the demo.

### Excluded from Demo

| Item | Why | When |
|------|-----|------|
| Real patient data | Fictional data only. No real PHI. | Never for demo |
| Native mobile app (iOS/Android) | Vue SPA, responsive. No App Store. | Roadmap |
| Multi-tenancy | Single organization. `organizations` table ready. | Roadmap |
| Appointment booking (real) | Concept shown, no calendar integration. | Roadmap |
| Payment / billing | No insurance, billing codes, or payments. | Roadmap |
| Vector DB / RAG | 1M context window sufficient. No Pinecone/Qdrant. | Evaluate post-demo |
| Drug-drug interactions (seeded) | Table exists, not seeded. DrugBank too much work. | Roadmap |
| Document upload pipeline | Table exists. Full upload + AI extraction not on demo. | Roadmap |
| Apple Health / Google Health | Researched (`docs/connectors.md`). Not implemented. | Roadmap |
| Full RBAC | Enum role for demo. Full permissions table later. | Roadmap |
| Full consent management | Basic fields on `patients`. Full `consents` table later. | Roadmap |
| E-prescribing | Shows prescriptions, doesn't generate legal Rx. | Out of scope entirely |
| FDA / CE certification | Not a medical device. No regulatory submission. | N/A |
| Doctor-side ambient scribing | Patient "reverse scribe" only. | Roadmap |
| Offline mode | Requires internet. | Roadmap |
| Push notifications (real) | Concept shown. No FCM/APNs. | Roadmap |

### Stretch Goal (if time permits)

| Item | Notes |
|------|-------|
| Multi-language UI | Architecture supports it (`preferred_language` in data model). Add at the very end if time allows. |

### Demo Deliverable (recap)

1. **Patient flow:** Landing → Demo → Scribe → Processing → Visit View → Chat → Meds → Feedback
2. **Doctor flow:** Dashboard → Patient detail → Chat audit → Response
3. **AI pipeline:** Transcript → Structured visit → Q&A → Explanations → Escalation
4. **Data:** FHIR R4, PostgreSQL, seeded PVCs + propranolol scenario
5. **API:** REST + Sanctum, SSE streaming, RxNorm proxy
6. **Compliance:** Audit logs, access control, encryption, disclaimer
