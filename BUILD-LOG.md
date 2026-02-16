# Development Journey — PostVisit.ai

> How a cardiologist built a clinical AI platform in 7 days, testing it on real hospital workflows between coding sessions.

## Overview

| Metric | Value |
|--------|-------|
| **Duration** | 7 days (Feb 10-16, 2026) |
| **Commits** | 349 |
| **Files** | 537 |
| **Lines of code** | ~54,000 |
| **Tests** | 262 (797 assertions) |
| **AI services** | 15 |
| **API endpoints** | 111 |
| **Documentation files** | 33 |
| **Developer** | 1 (physician + software engineer) |
| **Dev tool** | Claude Code (multiple parallel instances) |

## Development Setup

- **Primary machine**: MacBook Pro 16"
- **Secondary**: MacBook Air (lightweight edits on the go)
- **Server**: Hetzner VPS (EU) — multiple Claude Code instances running in parallel
- **Deployment**: Laravel Forge — US server for hackathon demo
- **Locations**: Home (Brussels), hospital (clinical ward), train (Brussels→Paris), airplane (CDG→SFO), San Francisco

## Day-by-Day Log

### Day 1 — Tuesday, Feb 10 | Research & Scaffolding

**Location:** Home, Brussels (evening CET)

**18:34** — First commit. But no code yet — the first 4 hours were pure research and clinical design:

- Wrote the full PRD (`docs/seed.md`) — not a quick spec but a physician reasoning through what data actually flows in a real clinical encounter
- Researched 9 drug databases across 3 continents before choosing RxNorm + OpenFDA + DailyMed (all public domain)
- Evaluated speech-to-text options: Whisper, Google Cloud STT, Deepgram Nova-3 Medical
- Studied clinical guideline licensing: ESC, AHA, NICE — which ones can be bundled in open source?
- Designed the FHIR-aligned data model (Patient, Encounter, Observation, Condition, MedicationRequest)

**21:31** — Full Laravel 12 + Vue 3 + PostgreSQL scaffold generated. First working app.

**22:58** — AI chat wired in with SSE streaming. Real Claude responses flowing.

**23:25** — First browser testing session. Fixed URL redirects, dosage formatting, markdown rendering.

**By midnight:** Working prototype — auth, patient/doctor roles, AI chat with streaming, medical API clients, 67 tests passing.

---

### Day 2 — Wednesday, Feb 11 | Hospital Clinical Testing

**Location:** Hospital (morning/afternoon)

**07:33** — Audio recording pipeline committed before leaving for the hospital: MediaRecorder → chunking → Whisper STT → AI SOAP note generation.

**Morning (hospital)** — First clinical evaluation session. Tested the Companion Scribe recording flow with consenting patients in real ward conditions:
- Discovered that the "save first, transcribe second" pattern was critical — network drops in the hospital meant recordings could be lost mid-transcription
- Found that audio chunking was necessary for consultations longer than 10 minutes
- Realized the consent flow needed to be front-and-center, not buried in settings
- Identified that medical term highlighting needed to work across multiple languages (patients speak French, Dutch, English)

*All clinical testing was conducted with explicit patient consent.*

**10:30** — Work from hospital. Committed Opus 4.6 extended thinking, prompt caching, and clinical guidelines integration — directly informed by morning observations.

**11:07** — Audio chunking for long recordings (30+ min) — solving the problem discovered in clinical testing.

**12:47** — Fixed a race condition in chunk rotation — each recorder instance needed its own data array to prevent cross-contamination between chunks.

**13:03** — Hardened the entire recording pipeline against data loss. Three separate commits addressing edge cases found during hospital testing.

**Evening (hospital)** — Returned to the hospital to film demo video segments in a real clinical environment with consenting patients. Captured screen recordings and clinical workflow footage for the hackathon submission video.

**Late evening** — Massive feature push: document attachments with AI analysis, lab results component, blood pressure monitoring, medical reference verification with PubMed.

**Night** — Multi-scenario demo engine, clinical guidelines knowledge base, QR code mobile upload. Running 3-4 Claude Code instances in parallel on the VPS.

**By midnight:** 120+ commits. Full patient experience working end-to-end.

---

### Day 3 — Thursday, Feb 12 | Home Evaluation & Feature Depth

**Location:** Home, Brussels

**00:02** — Overnight fixes: broken migration, API budget error. The system was being stress-tested continuously.

**Morning** — Systematic evaluation at home, working through every patient flow:
- Doctor dashboard with role switching
- HIPAA-inspired audit logging middleware
- 12 demo scenarios with synthetic patient profiles and AI-generated portraits
- Personal Medical Library with 5-step AI analysis pipeline

**10:06** — Audit trail committed: every PHI access logged with user, action, resource, IP, session ID, and PHI element categories. Directly inspired by HIPAA requirements from clinical experience.

**Afternoon** — Chat UX overhaul: hybrid Quick+Deep Reveal (Haiku instant answer + Opus deep analysis), resizable panel, auto-context from current visit section.

**Evening** — S3 storage migration, health profile enhancements, Connected Services redesign, SSE streaming fix (bypassed Anthropic SDK buffering with raw cURL for true token-by-token delivery).

**By midnight:** Doctor panel fully functional. Patient-doctor feedback loop working. 200+ commits.

---

### Day 4 — Friday, Feb 13 | Coding Between Coronarographies

**Location:** Hospital cath lab (day), train Brussels→Paris (evening)

**Morning/Afternoon (hospital)** — Coding between coronarography procedures in the catheterization laboratory. Second clinical evaluation session with consenting patients:
- Tested the full flow: recording → transcription → AI summary → patient Q&A
- Validated escalation detection with realistic patient descriptions
- Filmed segments for the demo video in clinical setting
- Tested audio capture in a high-noise interventional environment
- Screen Wake Lock API added after discovering phones sleep during long recording sessions

**09:30** — Wake Lock commit — a direct fix from hospital testing where an iPhone locked mid-recording.

**10:45** — Async transcription pipeline with S3 — decoupling upload from processing, because hospital WiFi was unreliable.

**15:00** - Vibe coded a teleprompter app with Claude Code to record some takes in the cathlab. It was a lot of fun and it worked surprisingly well.

**Evening (train)** — Boarded the train from Brussels to Paris. Continued coding on the move:
- Brand identity: logo placement across all pages
- Chat UI redesign: emerald responses, context pills, tag icons
- Multiple iterations on the Ask AI button icon (sparkle → logo → arrow → back to logo)

**Night (Paris)** — Demo video production. This was a major time investment:
- Scripting the voiceover, screen recording every flow, editing and compositing
- Re-recording when features changed, voiceover narration, final assembly
- Multiple iterations as the UI evolved faster than the video could keep up

**The demo video was the single biggest time sink of the project.**

---

### Day 5 — Saturday, Feb 14 | Paris → San Francisco

**Location:** Paris CDG → San Francisco (11-hour flight)

**09:12** — Demo login toggle and flight checklist committed before heading to the airport.

**In-flight coding** — Limited connectivity, but VPS-hosted Claude Code instances were a lifesaver — agents continued working on the server even with unstable in-flight WiFi. Focused on features that didn't require API calls:
- Patient-doctor messaging thread with unread badges
- Schedule Appointment invitation feature
- Doctor panel redesign
- UI polish: login gradient, weight chart, vitals colors

**17:28-18:28** — Landed in SF. Pushed accumulated commits, merged branches, fixed conflicts.

**By evening:** Patient-doctor communication loop complete. All core features implemented.

---

### Days 5-6 — Saturday-Sunday, Feb 14-15 | San Francisco — Jet Lag Marathon

**Location:** San Francisco

Massive jet lag (9-hour time difference Brussels→SF) turned into a productivity hack — coding through the night until 6 AM, sleeping a few hours, then back at it.

**Saturday night into Sunday** — Context Compaction, Tool Use in Education Generator, Adaptive Thinking, Deep Context with token counter. The Opus 4.6 integration features that demonstrate the full model capability.

**Significant time spent on demo video** — re-recording screen captures because the UI kept evolving, voiceover narration, editing, compositing, assembly. Every feature change meant re-shooting the affected segments. The video production consumed nearly as much time as the coding itself. Claude code helped a lot with the video production (e.g. with feature screens)

**Sunday afternoon** — Showcase slides, HR drop alerts, dead code cleanup, rate limiting, CLAUDE.md condensation.

**Sunday evening** — Final polishing pass. Pre-landing checklist: code formatting, documentation translation (Polish → English), security audit, healthcare compliance documentation, dead code cleanup, XSS hardening, authorization policies, dependency audit. Making sure everything is submission-ready.

---

### Day 7 — Monday, Feb 16 | Jet Lag Finishing Touches

**Location:** San Francisco

Another jet lag night — wide awake at 3 AM, might as well ship. Final day before submission deadline (15:00 EST).

- Fixed demo rate limiting that was too aggressive (users hitting 429s within minutes)
- Added Slack notifications for demo activity monitoring
- Re-seeded production with latest scenario data (HR trend was missing)
- Created Privacy Policy, Terms of Use, and Legal Notice pages — realized they were completely missing when clicking the consent link in Companion Scribe
- Fact-checked all legal page content against actual codebase (removed false claims about analytics, data export, and account deletion that didn't exist)
- Aligned Companion Scribe recording screen text
- Final demo video re-recording and upload
- README reorganization — Healthcare-Oriented Design section, feature hierarchy, correct specialty list
- Inserted missing lab results on production (creatinine, LDL, HDL, triglycerides, hemoglobin, BNP)
- Pre-landing audit: closed 13 GitHub issues, removed internal checklists, sanitized deployment docs
- Production deploy, server health verification, log review — all green for submission

---

## How It Was Built

### Development Workflow

1. **Clinical observation** — identify a real problem from hospital experience
2. **Specification** — describe the requirement (often in Polish — thinking and dictating in your native language removes the cognitive overhead of translation, letting you express complex clinical requirements faster)
3. **Claude Code builds** — multiple parallel instances on VPS, each working on separate modules
4. **Browser testing** — verify in Chrome, fix issues, iterate
5. **Clinical validation** — test with real patients (with consent) in hospital setting
6. **Feedback loop** — clinical insights feed back into the next iteration

### Claude Code as a Development Tool

Claude Code was used as an AI-powered development tool — not as a co-author. The workflow:

- **Multiple parallel instances** running on a Hetzner VPS, each in its own git worktree
- Up to 4-5 agents working simultaneously on separate modules (backend + frontend + docs)
- The developer directed all architectural decisions, clinical domain design, and product direction
- Claude Code executed: scaffolding, implementation, testing, documentation
- Every clinical decision — what data to show, how to explain terms, when to escalate — was human-driven

### Clinical Testing Protocol

Two hospital evaluation sessions (Day 2 and Day 4) with consenting patients:

- **Recording flow**: tested audio capture in real ward conditions (ambient noise, interruptions, varying consultation lengths)
- **AI accuracy**: verified SOAP note generation against physician expectations
- **Patient comprehension**: assessed whether AI explanations were understandable
- **Escalation detection**: validated that urgent symptoms triggered appropriate responses
- **Mobile experience**: tested on iPhone in clinical setting (discovered Wake Lock issue)

Every clinical test was conducted with explicit patient consent. No real patient data is stored in the application — all demo scenarios use fictional data.

### AI-Generated Demo Scenarios

Building convincing clinical demo scenarios required an entire AI production pipeline:

- **Patient portraits** — generated with FAL.ai Flux 2 Realism, iterated per scenario to match age, gender, and ethnicity of fictional patients
- **Animated portraits** — video animations of patient photos for the scenario picker hover effect
- **Clinical transcripts** — partially AI-generated visit dialogues, reviewed and corrected by a physician for clinical accuracy
- **Voice synthesis** — ElevenLabs text-to-speech for demo visit audio recordings, creating realistic doctor-patient conversations
- **SOAP notes** — AI-generated structured clinical notes from transcripts, physician-reviewed for medical accuracy
- **Medical terms extraction** — automated extraction and classification of clinical terminology from each scenario

The clinical scenarios were designed by the author based on real clinical experience — realistic presentations, typical lab values, common medication regimens, and authentic doctor-patient dialogue patterns. No real patients were used; every case is fictional but clinically plausible. Each of the 12 scenarios went through this full production pipeline to make the demo feel like a real clinical system — not a toy prototype.

### Key Technical Challenges Solved

| Challenge | Solution | Discovered |
|-----------|----------|------------|
| Audio lost during transcription | Save-first-then-transcribe pattern | Hospital Day 2 |
| Phone sleeps during recording | Screen Wake Lock API | Hospital Day 4 |
| Chunk rotation race condition | Closure isolation per recorder | Hospital Day 2 |
| Hospital WiFi drops | Async pipeline with S3 | Hospital Day 4 |
| SDK buffers SSE stream | Raw cURL bypass for token-by-token delivery | Day 3 |
| AI too aggressive on escalation | Thinking-backed evaluation with Opus | Day 2 |
| SOAP notes looked artificial | Physician-reviewed prompt iterations | Days 2-4 |
| N+1 FDA API calls | Caching + eager loading | Day 3 |

## Tools & Environment

| Tool | Role |
|------|------|
| **Claude Code** | AI development tool (multiple parallel instances) |
| **Laravel Herd** | Local PHP development server |
| **Laravel Forge** | Production deployment |
| **GitHub** | Version control, CI |
| **Linear** | Project management |
| **Craft** | Documentation and planning |
| **FAL.ai (Flux 2 Realism)** | AI-generated patient portraits and animations |
| **ElevenLabs** | Voice synthesis for demo visit audio recordings |

