# Demo Guide

Step-by-step walkthrough of PostVisit.ai for hackathon judges. Total demo time: ~10 minutes.

## Prerequisites

- **Browser:** Chrome or Firefox (latest)
- **URL:** `https://postvisit.test` (local) or deployed URL
- **Demo data:** Must be seeded (`herd php artisan db:seed --class=DemoSeeder`)
- **No account needed** -- demo mode provides instant access

## Demo Flow Overview

```
Landing Page --> Login Page --> Scenario Picker --> Patient Profile
    --> Visit Summary (SOAP + Term Highlighting + Chat)
    --> Medications --> Document Upload --> Settings (AI Tiers)
    --> Doctor Dashboard --> Patient Detail
```

---

## Step 1: Landing Page

**URL:** `/`

The landing page introduces PostVisit.ai with the tagline: *"The bridge between your visit and your health."*

Click **"Try Demo"** or **"Sign In"** to proceed.

**What to show:** Clean branding, medical disclaimer at the bottom, two entry points.

---

## Step 2: Login Page (Demo Entry)

**URL:** `/login`

The login page features a prominent **"Try the Demo"** section above the standard login form, with a glowing green border animation to draw attention.

Two demo entry options:
- **"Sign in as Patient"** -- navigates to the Scenario Picker (`/demo/scenarios`)
- **"Sign in as Doctor"** -- logs in directly as a demo doctor and redirects to the Doctor Dashboard

No password or account needed.

**What to show:** One-click demo access, no friction.

---

## Step 3: Scenario Picker

**URL:** `/demo/scenarios`

A card-based grid showing available clinical scenarios. Each card displays:
- Patient name, age, and gender
- Clinical specialty and condition
- Brief description of the case

Click any scenario card to create a fresh demo session with realistic seeded data. The system creates a demo user, seeds visit data (SOAP note, transcript, conditions, prescriptions, observations), and logs in automatically.

After selecting a scenario, you are redirected to the **Patient Profile**.

**What to show:** Multiple clinical scenarios demonstrating the system's versatility.

---

## Step 4: Patient Profile

**URL:** `/profile`

Displays the patient's profile with:
- Name, email, avatar initials
- **Visit History** -- list of all visits with date, type, practitioner, and specialty
- **"Record New Visit"** button linking to the Companion Scribe

Click on any visit in the list to open the Visit Summary.

**What to show:** Visit history with rich metadata, clean patient-facing interface.

---

## Step 5: Visit Summary (Core Feature)

**URL:** `/visits/:id`

This is the main screen. It uses a two-column layout:
- **Left column:** Visit data (scrollable)
- **Right column:** AI Chat panel (sticky on desktop, tab-switchable on mobile)

### Left Column Content (top to bottom):

1. **Visit header** -- date, visit type, practitioner name and specialty
2. **Quick Summary** -- auto-generated one-line summary from the SOAP note
3. **SOAP Note sections** -- each section is a collapsible card:
   - Chief Complaint
   - History of Present Illness
   - Reported Symptoms (Review of Systems)
   - Physical Examination
   - Assessment
   - Plan
   - Follow-up
4. **Doctor's Recommendations** -- extracted action items from the Plan section, numbered
5. **Next Actions Checklist** -- interactive checkboxes combining follow-up items and medication reminders
6. **Test Results & Observations** -- expandable section with lab values (cholesterol, K+, TSH, etc.)
7. **Diagnosis** -- conditions with ICD-10 codes
8. **Medications Prescribed** -- dosing, frequency, special instructions
9. **Patient Attachments** -- uploaded documents (images, PDFs) with AI analysis
10. **AI-Extracted Clinical Entities** -- from transcript analysis (symptoms, diagnoses, medications, test results, vitals)
11. **Visit Transcript** -- raw or diarized transcript with speaker labels

### Medical Term Highlighting (Key Feature)

Within each SOAP section, medical terms are highlighted with a green underline. These are extracted by the `TermExtractor` AI service with character-level offsets.

**Demo action:** Tap any highlighted term.

A **TermPopover** appears showing:
- The term name
- A brief definition
- An **"Explain"** button

Clicking "Explain" sends a request to the `MedicalExplainer` AI service, which streams a patient-friendly explanation in the context of this specific visit.

**What to show:** Tap "premature ventricular complexes" or "propranolol" to see contextual explanations.

---

## Step 6: AI Chat (Core Feature)

**URL:** `/visits/:id` (right column or mobile tab)

The ChatPanel provides a conversational interface where patients can ask questions about their visit.

**How it works:**
1. Type a question (e.g., "Why was propranolol prescribed?")
2. The AI assembles full visit context (SOAP note, transcript, patient record, medications, FDA safety data, clinical guidelines)
3. Response streams in real-time via Server-Sent Events (SSE)
4. On Opus 4.6 tier, a **ThinkingIndicator** shows when Claude is using extended thinking for clinical reasoning

**Demo questions to try:**
- "What are PVCs and should I be worried?"
- "What are the side effects of propranolol?"
- "When should I call my doctor?"
- "Can I drink coffee with this medication?"
- "What did my blood test results mean?"

**Safety demo:** Type "I'm having chest pain" to see the escalation detector in action. Critical symptoms trigger an immediate emergency response directing the patient to call 911.

**What to show:** Streaming responses, contextual answers, source awareness, escalation safety.

---

## Step 7: Medications Detail

**URL:** `/visits/:id/meds`

Click on the "Medications Prescribed" section or navigate directly. Shows:
- **MedCard** for each prescription with:
  - Drug name (generic + brand)
  - Dosing and frequency
  - Route of administration
  - Special instructions
  - Interaction warnings
- Chat integration for medication-specific questions

**What to show:** Rich medication data sourced from RxNorm and OpenFDA.

---

## Step 8: Document Upload (QR Code)

From the Visit Summary, scroll to the **Patient Attachments** section.

- **Desktop:** Drag and drop images/PDFs, or click to browse
- **Mobile:** Generate a QR code that opens a mobile upload page (no login required, token-based)

Uploaded documents are analyzed by the `DocumentAnalyzer` AI service (Claude vision) which extracts structured findings from lab reports, prescriptions, or medical images.

**What to show:** Upload a lab result image, see AI-generated analysis appear.

---

## Step 9: AI Tier Comparison

**URL:** `/settings`

Navigate to Settings from the patient sidebar. The **AiTierSelector** component shows three tiers:

| Tier | Model | Features |
|------|-------|----------|
| **Opus 4.6** | claude-opus-4-6 | Extended thinking, prompt caching, clinical guidelines |
| **Sonnet** | claude-sonnet-4-5 | Prompt caching, faster responses |
| **Haiku** | claude-haiku-4-5 | Fastest, lowest cost |

Switch between tiers and ask the same question in chat to compare answer quality and reasoning depth. On Opus 4.6, the ThinkingIndicator shows clinical reasoning before the response.

**What to show:** Switch from Opus to Haiku, re-ask a clinical question, compare depth and quality.

---

## Step 10: Doctor Dashboard

**URL:** `/doctor`

Go back to the login page and click **"Sign in as Doctor"**, or navigate directly if already logged in as doctor.

The Doctor Dashboard shows:
- **Alerts panel** -- patients requiring attention (weight gain, elevated BP trends)
- **Stats overview** -- total patients, active visits, pending alerts
- **Patient list** -- searchable table with last visit date and status

### Patient Detail View

**URL:** `/doctor/patients/:id`

Click any patient to see:
- Patient demographics and health profile
- Visit history
- Engagement metrics (chat usage, question count)
- Chat audit log (all AI interactions for oversight)
- Observation trends (BP, weight charts)

**What to show:** Clinical oversight, AI interaction auditing, trend visualization.

---

## Troubleshooting

### Demo data not loading
```bash
herd php artisan db:seed --class=DemoSeeder
```

### Blank page after login
```bash
bun run build
```

### API errors (500)
Check the Laravel log:
```bash
tail -f storage/logs/laravel.log
```

### Chat not streaming
- Verify the browser supports SSE (EventSource API)
- Check that the API returns `Content-Type: text/event-stream`
- Look for CORS or mixed-content issues in browser console

### Term highlights not showing
- Verify `visit_notes.medical_terms` is populated (check DB)
- If empty, re-run term extraction: the system processes terms when a visit note is created

### Scenario picker empty
```bash
herd php artisan db:seed --class=DemoScenarioSeeder
```
