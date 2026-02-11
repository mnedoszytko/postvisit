# Architecture Decisions — Discussion Log

This document records all decisions made during the design of PostVisit.ai.

## Date: 2026-02-10

### Decision 1: Repository Structure — Integrated Laravel + Vue Monorepo
**Status:** Accepted (updated 2026-02-10)

Single repo, integrated Laravel + Vue (Vue in `resources/js/`, not a separate directory). See Decision 19.

```
postvisit/
├── app/                # Laravel application (controllers, models, services)
├── resources/js/       # Vue 3 SPA (integrated)
├── docs/               # Working documentation
├── prompts/            # System prompts for Opus (versioned like code)
├── demo/               # Demo data, scenarios, seed data
├── database/           # migrations, seeders, factories
├── tests/              # PHPUnit tests (67 tests)
├── CLAUDE.md
├── README.md
├── LICENSE
├── SECURITY.md
└── .env.example
```

### Decision 2: Demo Data — Written by a Physician
**Status:** Accepted

The user (a physician) will write realistic discharge notes, recommendations, and patient data. The scenario from seed.md (premature ventricular contractions, propranolol) is the basis, but will be expanded into a full, credible medical document.

### Decision 3: Ambient Scribing / Transcription — Must Be in Demo
**Status:** Accepted

Transcription of the doctor-patient conversation is a critical demo element. Without it, the demo has no impact. The system must demonstrate how the transcript serves as a source of context for the AI.

### Decision 4: Doctor-in-the-Loop — Both Views
**Status:** Accepted

The demo will include:
- **Patient view** — main screen with visit summary, Q&A, explanations
- **Doctor view** — dashboard with feedback, context

The demo video will show both sides.

### Decision 5: Hosting — Forge + Hetzner
**Status:** Accepted

- Deploy via Laravel Forge
- Server on Hetzner
- Claude Code has access to Hetzner via API
- Local development on MacBook Air with Herd

### Decision 6: AI Context — Visit + Clinical Guidelines
**Status:** Accepted

The context for Opus 4.6 is not limited to visit data. It also includes:
- Data from the specific visit (discharge notes, medications, tests, transcript)
- **Clinical guidelines** (e.g. ESC — European Society of Cardiology, AHA — American Heart Association)
- Allows Opus to provide evidence-based medicine answers in the context of this specific patient

This is a strong point for the hackathon — demonstrates creative use of Opus 4.6's 1M token context window.

### Decision 7: Required Repo Files for the Hackathon
**Status:** Accepted

Based on analysis of hackathon requirements and best practices:

| File | Purpose | Priority |
|------|---------|----------|
| `README.md` | First thing judges see — must be excellent | Critical |
| `LICENSE` | Open source required — MIT | Critical |
| `.env.example` | Shows professional approach to secrets management | Critical |
| `CLAUDE.md` | Anthropic-specific — demonstrates deep integration with Claude Code | Critical |
| `SECURITY.md` | Healthcare AI — security is a must-have | High |
| `docs/architecture.md` | Demonstrates thoughtful design | High |
| Disclaimer in README | "Demo only, no real patient data, not for clinical use" | Critical |

### Decision 8: Demo Video Scenarios
**Status:** Pending

User will provide 2 video scenarios. Waiting for data.

### Decision 9: Hackathon — Key Facts
**Status:** Information

- **Hackathon:** Built with Opus 4.6 (Anthropic + Cerebral Valley)
- **Dates:** February 10–16, 2026
- **Deadline:** Monday, February 16, 3:00 PM EST
- **Prizes:** $100K in API credits ($50K/1st, $30K/2nd, $10K/3rd + 2x $5K special)
- **Special prizes:** "Most Creative Opus 4.6 Exploration" and "The Keep Thinking Prize"
- **Judges:** 6 people from Anthropic (Boris Cherny, Cat Wu, Thariq Shihpar, Lydia Hallie, Ado Kukic, Jason Bigman)
- **Winners showcase:** February 21 in SF
- **Submission:** GitHub repo (public) + demo video (**max 3 min**) + written summary (**100–200 words**) via CV platform
- **Judging Stage 1:** Async, Feb 16–17 (all submissions)
- **Judging Stage 2:** Live, Feb 18 12:00 PM EST (top 6 only) → winners at 1:30 PM
- **Full rules:** `docs/hackathon-rules.md`

### Decision 10: Model Policy — Sonnet OK for Tests
**Status:** Accepted

- **Production / demo:** Opus 4.6
- **Tests / development:** Sonnet is OK (cost optimization)
- **Subagents (Task tool):** always Opus

### Decision 11: AI Context — Deferred to Separate Discussion
**Status:** Deferred

AI context sources (visit data, clinical guidelines, guardrails) will be discussed in detail in a dedicated session. Removed from CLAUDE.md until finalized.

### Decision 12: PHP 8.4
**Status:** Accepted

PHP 8.4 — no discussion needed. Database and cache to be decided during scaffolding.

### Decision 13: Database — PostgreSQL
**Status:** Accepted (changed from "Deferred")

PostgreSQL — decision based on analysis of data-model.md:
- **jsonb** — native indexing on `specialty_data`, `extracted_entities`, `diarized_transcript`
- **tsvector** — full-text search on transcripts and clinical notes
- **UUID** — native type (not varchar)
- **Partitioning** — audit_logs partitioned by month
- Industry standard for healthcare (HIPAA/SOC2)

Cache and CSS framework — to be decided during scaffolding.

### Decision 14: Agent Teams — Enabled
**Status:** Accepted

Enabled experimental feature `CLAUDE_CODE_EXPERIMENTAL_AGENT_TEAMS` in `~/.claude/settings.json`. Allows spawning agent teams that work in parallel and can communicate with each other (e.g. backend + frontend + devil's advocate). New Opus 4.6 feature — active after terminal restart.

### Decision 15: Demo Video — Orientation and Subtitles
**Status:** To Be Decided

**Orientation:**
The video must show two things: the application UI (iOS mobile) and system architecture/flow.

Options:
- **Horizontal (16:9)** — standard for software demos. Easier to show split screen (phone mockup + architecture diagram side by side). Judges watch on laptops. Most hackathon videos are landscape.
- **Vertical (9:16)** — natural for mobile apps. But judges are unlikely to watch on phones, and it's hard to fit text/diagrams alongside.
- **Horizontal with phone mockup in center** — compromise: landscape frame, phone with app in the center, context/architecture on the sides.

**Subtitles / captions:**
- Must have — judges may watch without sound
- Burned-in (hardcoded in video) vs. separate file (.srt)

Tools to consider:
- **CapCut** — free, auto-captions, good styles
- **Descript** — transcription + text editing = video editing
- **DaVinci Resolve** — free, professional, but steep learning curve
- **Whisper + ffmpeg** — generate .srt with Whisper, burn-in via ffmpeg (fully open source pipeline)

Subtitle style: short, keyword-heavy, explaining what's happening on screen (not a full voiceover transcript).

### Decision 16: Data Model — FHIR R4, diagnostic_reports Removed
**Status:** Accepted

Data model in `docs/data-model.md` — 17 tables, FHIR R4 aligned. `diagnostic_reports` removed (duplication with observations + documents + visit_notes). `roles` simplified to enum for demo. `consents` table excluded from demo.

### Decision 17: Medications — RxNorm API + Local Cache
**Status:** Accepted

The `medications` table acts as a local cache. Propranolol seeded (demo reliability). Other medications fetched from RxNorm API on-demand (`rxnav.nlm.nih.gov/REST/`). Judges can search for any medication.

### Decision 18: API — REST for Demo, Interoperability-First
**Status:** Accepted

Demo: Laravel REST API + Sanctum. But the architecture from day zero assumes:
- Interoperability (FHIR R4 export endpoints — roadmap)
- Agent-friendly API (GraphQL layer — roadmap)
- Ecosystem integration (webhooks, CDS Hooks — roadmap)

PostVisit.ai is NOT a standalone island — it's a scalable product in the healthcare ecosystem.

### Decision 19: Integrated Laravel + Vue (Changed from Separate Directories)
**Status:** Accepted (2026-02-10)

Changed from `backend/` + `frontend/` to integrated architecture:
- Vue 3 in `resources/js/` (Laravel standard)
- Zero CORS issues (same-origin)
- Simpler auth (Sanctum cookie-based)
- Faster development for hackathon
- API (`/api/v1/`) remains standalone and fully accessible

### Decision 20: Bun Instead of npm
**Status:** Accepted (2026-02-10)

Bun as package manager instead of npm. Faster install, faster build. Bun 1.3.9.

### Decision 21: Cache and Queue — Database Driver
**Status:** Accepted (2026-02-10)

Database driver (PostgreSQL) for cache and queue. Simpler than Redis, sufficient for hackathon. Zero additional infrastructure.

### Decision 22: Tailwind CSS v4 (not v3)
**Status:** Accepted (2026-02-10)

Laravel 12 ships with Tailwind CSS v4. Using native integration, no separate tailwind.config.js needed (CSS-first config via `@theme`).

### Decision 23: PrimeVue 4 as UI Component Library
**Status:** Accepted (2026-02-10)

PrimeVue 4 + Aura theme for production-quality UI. Replaces custom Tailwind-only components. See POST-1.

### Decision 24: Linear for Project Management
**Status:** Accepted (2026-02-10)

Linear (team POST in medduties workspace) for issue tracking. GraphQL API access via `$LINEAR_API_KEY`. All issues tagged `agent-ready` can be worked on autonomously.

### Decision 25: Voice Chat via OpenAI Whisper + TTS
**Status:** Accepted (2026-02-10)

MediaRecorder in browser → POST to Laravel → proxy to OpenAI Whisper API for STT. Optional TTS via OpenAI TTS API. See POST-16.

### Decision 26: Testing Strategy — PHPUnit + SQLite In-Memory
**Status:** Accepted (2026-02-10)

67 feature tests, 175 assertions, <1s runtime. SQLite in-memory for speed. PostgreSQL-specific features (ilike) handled with conditional logic for test compatibility.

## Date: 2026-02-11

### Decision 28: Audio Upload — Save-First-Then-Transcribe Pattern
**Status:** Accepted (2026-02-11)

**Problem:** 21-minute recording lost because audio was only in browser memory when transcription failed. Need a resilient upload pipeline.

**Options:**
- **A) Single endpoint (store + transcribe atomically)** — If transcription fails, audio may be lost unless stored first. Simple but risky.
- **B) Save-first-then-transcribe (3-phase)** — Phase 1: save all audio to server. Phase 2: transcribe. Phase 3: combine and process. Audio survives any failure after Phase 1.
- **C) Background upload during recording** — Stream audio to server in real-time during recording. Most resilient but complex (WebSocket/chunked upload during MediaRecorder capture).

**Decision:** Option B — save-first-then-transcribe.

**Rationale:**
- Audio persists on server before any transcription attempt
- If Whisper fails, user can retry without re-recording
- Simple 3-phase flow: save → transcribe → combine
- Works for both single-segment and multi-segment recordings
- No real-time streaming complexity (Option C is future roadmap)
- Visit ID persisted for retry — no orphaned resources

### Decision 29: Recording Pipeline Hardening — 4 Defensive Measures
**Status:** Accepted (2026-02-11)

Four hardening measures added to prevent recording data loss:
1. **Await onstop Promise** — `stopRecording()` awaits MediaRecorder's `onstop` event before allowing user to proceed. Prevents race condition.
2. **Closure-scoped chunk data** — Each `createRecorder()` uses its own scoped array, eliminating shared-state race condition during chunk rotation.
3. **beforeunload warning** — Browser warns when closing tab during recording or upload.
4. **Retry reuses visitId** — Failed uploads retry to the same visit, not a new one.

### Decision 27: Medical Term Highlighting — jsonb Offsets, Not Inline HTML or Real-Time Extraction
**Status:** Accepted (2026-02-11)

**Problem:** PRD user story P3 requires individual medical terms in SOAP notes to be highlighted and clickable (tap-to-explain). Three approaches considered.

**Options:**
- **A) Store terms with character offsets in jsonb** — AI extracts terms once when note is created, stores them as `{term, start, end}` objects in a `medical_terms` jsonb column on `visit_notes`. Frontend renders highlights at display time using offsets.
- **B) Inline HTML in SOAP text** — Wrap terms in `<span>` tags directly in the stored SOAP text. Simpler frontend but corrupts the signed clinical note text, makes search/export unreliable, and mixes presentation with data.
- **C) Real-time AI extraction on each page load** — No stored terms; call AI every time the patient views the note. Consistent but expensive (~$0.05 per view), adds 2-3s latency, and results may vary between calls.

**Decision:** Option A — jsonb offsets.

**Rationale:**
- One-time AI cost per note (extraction at processing time or hardcoded in demo seed)
- Zero latency on page load — terms are pre-computed and delivered with the visit response
- Terms tied to immutable signed notes — offsets are stable because SOAP text never changes after signing
- Clean separation of data (SOAP text) and metadata (term positions)
- Frontend validates offsets client-side with fallback to string search for robustness
- Survives server restarts, works offline, no AI dependency at read time
