# Features Required for Demo Video

> Extracted from the video-script-v4.docx scenario. Every feature must WORK on the recording.
> Status: draft — the script is not yet finalized.

## Screen recordings to prepare

The script requires screen recordings in scenes 3, 4, and 5. This defines the MVP scope.

### Scene 3 — Patient App (phone)

| # | Feature | Description | Priority |
|---|---------|-------------|----------|
| 3A | Notification | "PostVisit.AI: Your visit summary is ready" — push/in-app | Must |
| 3B | Visit summary | Diagnosis, medications, next steps — plain language | Must |
| 3C | Tap-to-explain | Click on "Paroxysmal Ventricular Contractions" → chat opens | Must |
| 3D | Q&A: "What causes this?" | AI responds: stress, caffeine, lack of sleep | Must |
| 3E | Q&A: "What is propranolol?" | AI responds: beta-blocker, side effects (fatigue, cold hands, dizziness), "do not stop abruptly" | Must |

### Scene 4 — Product Deep Dive (screen recording + PiP)

| # | Feature | Description | Priority |
|---|---------|-------------|----------|
| 4A | Wearable integration | Apple Watch data synced — heart rhythm, PVC detection | Should |
| 4B | Lab results upload | Blood work — cholesterol, potassium, thyroid + relevance notes | Should |
| 4C | Follow-up plan / timeline | BP check 2 weeks, echo 3 months — visual timeline | Should |

### Scene 5 — Doctor Dashboard

| # | Feature | Description | Priority |
|---|---------|-------------|----------|
| 5A | Doctor dashboard | Patient insights, flags, alerts — overview | Must |
| 5B | Patient questions feed | What the patient asked, what answers they received | Must |
| 5C | 2AM alert example | System recognizes pattern, cross-references with medications and labs, alerts doctor | Should |

## Priority summary

### Must have (no demo without these)
1. Visit summary screen (diagnosis + meds + next steps)
2. Tap-to-explain (click term → chat)
3. Q&A chat with AI (min. 2 questions: cause + medication)
4. Doctor dashboard (patient insights + questions feed)
5. Notification (can be a mockup)

### Should have (strengthens demo, but video works without these)
6. Wearable integration (Apple Watch)
7. Lab results upload + analysis
8. Follow-up timeline
9. 2AM alert scenario

## Ambient scribing (implied, not recorded)

The script assumes the phone was recording the visit (Scene 3: "my phone was on the table during the visit"). This does not require a screen recording — all that's needed is:
- 2-second flashback: phone on desk, recording icon
- Overlay: "* With mutual consent of doctor and patient"

But the backend MUST have an endpoint that accepts a transcript and generates a visit summary.

## Demo data needed

| Data | Source | Format |
|------|--------|--------|
| Visit transcript (cardiologist + patient) | Written by Nedo (physician) | Text |
| Discharge summary / visit summary | Written by Nedo | Text |
| Scenario: PVCs, propranolol 40mg 2x/day | seed.md | — |
| Apple Watch mock data (HR, PVC events) | Mock / generated | JSON |
| Lab results mock (cholesterol, K+, TSH) | Mock / generated | JSON |
| Doctor dashboard mock (patient list, alerts) | Mock / generated | JSON |
