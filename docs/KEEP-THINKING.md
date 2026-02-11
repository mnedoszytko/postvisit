# The Keep Thinking Log — PostVisit.ai

> How a doctor and an AI built a clinical AI system in 6 days, iterating relentlessly until it was right.

## Why This Document Exists

This is a living record of every significant iteration, pivot, dead end, and breakthrough during the development of PostVisit.ai. It demonstrates the depth of thinking that went into understanding the clinical process, the patient experience, and the technical architecture — not just building features.

**Hackathon:** Built with Opus 4.6 (Feb 10–16, 2026)
**Team:** 1 physician-developer + Claude Opus 4.6 (multi-agent)
**Commits:** 47+ across 6 branches
**Documents produced:** 15+ research/design docs before writing a single line of product code

---

## The Timeline

### Day 0 — Before Code: Understanding the Problem (Feb 10, afternoon)

Before touching any code, we spent **4+ hours** on pure research and design. The question wasn't "how do we build this?" but "what does a patient actually need after leaving the doctor's office?"

**Iteration 1: Problem framing**
Started with a broad idea: "AI that helps patients after visits." Too vague. Iterated through 3 problem statement versions until we arrived at the core insight:

> The patient doesn't need a health chatbot. They need the context of **this specific visit** — explained simply, available when they need it, and connected to their doctor.

This single constraint — **visit-anchored, not generic** — shaped every architectural decision that followed.

**Iteration 2: Clinical data deep dive**
Wrote `docs/seed.md` — a full working document covering the clinical scenario, compliance requirements, and product scope. This wasn't a quick spec — it was a physician reasoning through what data actually flows in a real clinical encounter:
- What does a discharge note contain? What's missing from it?
- What does the patient actually hear vs. what the doctor says?
- Where does information get lost? (Answer: everywhere)

**Iteration 3: Drug database research**
Before choosing any drug data provider, we researched 9 databases across 3 continents (`docs/drug-databases-research.md`):
- RxNorm (US), OpenFDA, DailyMed — public domain, free APIs
- DrugBank — richest data but license restrictions
- WHO ATC Classification — international coverage
- Polish drug databases (BLOZ) — for international demo potential
- EMA (European Medicines Agency) — EU coverage

We evaluated each on: data quality, license (open source requirement), API availability, patient-facing content quality, and international drug coverage. This research directly influenced the decision to use RxNorm as the primary identifier + OpenFDA for safety data + DailyMed for drug labels.

**Iteration 4: Clinical knowledge sources**
Researched how to bring evidence-based medicine into the AI context (`docs/clinical-sources.md`):
- Evaluated ESC, AHA, NICE, WHO guidelines — license compatibility with open source
- Calculated token requirements: a full ESC cardiology guideline = ~65,000–100,000 tokens
- Realized Opus 4.6's 1M context window means we can fit 4–8 full guidelines + patient data + conversation history
- Designed the "Evidence Search Agent" architecture — Claude plans queries, parallel API calls to PubMed/OpenFDA/Europe PMC, then synthesizes with citations
- Documented Anthropic Prompt Caching economics: 90% token savings on cached guidelines

**Iteration 5: Speech-to-text research**
Deep research into ambient scribing approaches (`docs/stt-scribing.md`):
- Whisper (open source, self-hosted, HIPAA-safe) vs Google Cloud STT (medical models) vs iOS native Speech Framework
- Discovered WhisperX (Whisper + Pyannote for speaker diarization) as best self-hosted option
- Evaluated medical-specific STT: Deepgram Nova-3 Medical (3.45% WER), AssemblyAI Slam-1
- Designed a provider-agnostic adapter pattern — swap STT provider via `.env`

**Key insight realized during research:** The "Reverse Scribe" concept — where the **patient** initiates recording, not the doctor — is a genuine differentiator. Every existing ambient scribe product (Nuance DAX, Abridge, Nabla) is doctor-side. Patient-initiated recording flips the power dynamic.

### Day 0 — Evening: Architecture & Scaffolding (Feb 10, 18:00–22:00)

**Iteration 6: Data model design**
Wrote `docs/data-model.md` — 17+ tables, FHIR R4 aligned. Major iterations:
- Started with 20+ tables, realized `diagnostic_reports` duplicated `observations` + `visit_notes` → removed
- Debated `roles` table vs enum → chose enum for hackathon simplicity (Decision #16)
- Deliberated on PostgreSQL vs MySQL → chose PostgreSQL for: jsonb (specialty_data), tsvector (transcript search), native UUID, healthcare industry standard (Decision #13)
- Designed `specialty_data` as jsonb column — allows EKG intervals, echo measurements, any specialty-specific data without schema changes

**Iteration 7: Product Requirements Document**
Full PRD (`docs/prd.md`) with 11 sections — not just features but:
- Context sources taxonomy (5 layers)
- Compliance framework (HIPAA, GDPR, SOC 2)
- AI guardrails specification
- Out-of-scope definition (what the system explicitly does NOT do)

**Iteration 8: 26 architecture decisions logged**
Every fork in the road was documented in `docs/decisions.md`:
- Decision 1: Monorepo vs separate repos → Monorepo (zero CORS)
- Decision 13: PostgreSQL over MySQL → jsonb, tsvector, UUID, healthcare standard
- Decision 17: RxNorm + local cache → Propranolol seeded, rest fetched on-demand
- Decision 19: Integrated Vue in Laravel vs separate frontend → Integrated (same-origin Sanctum)
- Decision 22: Tailwind v4 (not v3) → CSS-first config
- Decision 25: Voice chat via Whisper → MediaRecorder → Laravel → OpenAI API
- ...and 20 more

**Iteration 9: Full-stack scaffold**
Laravel 12 + Vue 3 + PostgreSQL scaffolded with:
- 18 Eloquent models, all with UUIDs and factories
- 22 migrations
- FHIR-aligned naming (Visit=Encounter, Prescription=MedicationRequest, Observation)
- 8 AI system prompts written as versioned files in `prompts/`

### Day 0 — Night: The Overnight Build (Feb 10, 22:00 – Feb 11, 07:00)

Deployed multi-agent teams. `docs/overnight-plan.md` captures the execution:

**Phase 1: AI Works For Real (Critical)**
- Wired Anthropic SDK into ChatController — real Opus 4.6 responses
- SSE streaming for chat and explain endpoints
- Hit first major bug: `anthropic-ai/laravel` facade doesn't exist → built bare SDK client
- Lesson logged in `docs/lessons.md` (Lesson #8: Sanctum TransientToken has no delete())

**Phase 2: Feature branches (parallel agents)**
- `feature/voice-chat` — MediaRecorder + Whisper STT
- `feature/primevue` — PrimeVue 4 + Aura theme
- `feature/recording-animations` — 3 animation variants (ripples, waveform, orbit)

**Phase 3: Medical APIs**
- Built 4 medical API clients: OpenFDA, DailyMed, RxNorm, NIH Clinical Tables
- Wired FDA adverse events data into AI context (Layer 5 in ContextAssembler)
- New endpoints: drug search, interactions, adverse events, drug labels

**Phase 4: Polish & testing**
- Full Chrome browser automation test of patient and doctor flows
- Found and fixed 3 bugs during testing (URL redirect, dosage format, markdown rendering)
- 67 tests, 175 assertions, all passing

### Day 1 — Morning: Deepening (Feb 11, 07:00–09:00)

**Iteration 10: Reverse AI Scribe pipeline**
Built the full pipeline: audio recording → Whisper STT → AI processing → SOAP note generation. This is the "Reverse Scribe" differentiator — patient records their own visit.

**Iteration 11: Chat UX iteration**
Three rounds of chat interface improvements:
- Round 1: Basic message send/receive
- Round 2: Added thinking indicator (animated dots while AI processes)
- Round 3: Welcome screen with suggested questions, source citations on every AI response

**Iteration 12: Medical term highlighting**
Implemented inline medical term detection — when the AI response contains medical terminology, terms are highlighted and clicking shows an instant popover definition. This was iterated twice — first version used regex, second version improved term boundary detection.

**Iteration 13: Recording visualization**
Explored 5 different recording animation approaches:
- CSS ripple animation
- CSS waveform bars
- CSS orbital dots
- CSS pulse ring
- Three.js 3D terrain visualizer (chosen — most distinctive)

The Three.js visualizer responds to recording state — calm terrain during silence, active deformation during speech. This level of polish shows depth of execution.

**Iteration 14: Multi-agent safety**
After running parallel agents overnight, we discovered merge conflicts and race conditions. Documented a formal merge strategy:
- All agent branches go through PR + codex review
- Squash merge to keep history clean
- Mandatory `herd php artisan test` + `bun run build` before merge

---

## Deep Iterations on Clinical Understanding

### The Context Assembly Problem

The single most iterated-on component. The AI needs to understand the visit deeply enough to answer patient questions accurately — but not so broadly that it hallucinates beyond the visit scope.

**Version 1:** Dump everything into the prompt. Result: AI answered questions about conditions the patient didn't have.

**Version 2:** 3-layer context (visit data, patient record, guidelines). Better, but AI couldn't distinguish between visit findings and historical data.

**Version 3:** 5-layer context with explicit boundaries:
```
Layer 1: Visit data (SOAP note, observations, prescriptions) — PRIMARY
Layer 2: Patient health record (conditions, history) — SECONDARY
Layer 3: Clinical guidelines (ESC, AHA) — REFERENCE
Layer 4: Medication details (RxNorm, interactions) — ENRICHMENT
Layer 5: FDA safety data (adverse events, drug labels) — SAFETY
```

Each layer has explicit instructions: "This is reference material. Do not present it as if the doctor said it." This prevents the AI from confusing evidence-based general knowledge with visit-specific findings.

**Version 4 (current):** Added transcript as highest-priority context when available. The transcript captures nuance that discharge notes miss — the doctor's tone when saying "this is usually benign," the patient's specific concerns, the reassurance given.

### The Escalation Detection Problem

When should the AI say "call your doctor"? This required clinical reasoning:

**Attempt 1:** Keyword list (chest pain, bleeding, etc). Too aggressive — flagged normal post-visit descriptions.

**Attempt 2:** AI-only classification. Too expensive (API call for every message) and too slow.

**Attempt 3 (current):** Two-tier system:
- Fast path: keyword detection for obvious emergencies (15 terms)
- AI path: Sonnet (not Opus — cost optimization) classifies ambiguous cases
- Cost: ~$0.01 per escalation check vs $0.15 for Opus

### The "Don't Be a Doctor" Problem

The hardest guardrail to get right. The system must be helpful without practicing medicine.

**Iteration 1:** "Never give medical advice." Result: AI refused to explain anything. Useless.

**Iteration 2:** "Explain what the doctor said, don't add new recommendations." Better, but AI still hedged every answer with excessive disclaimers.

**Iteration 3 (current):** Nuanced prompt with specific behavioral rules:
- "You may explain medical concepts in simple terms"
- "You may describe what is written in the visit record"
- "You must NOT suggest actions the doctor didn't recommend"
- "If the patient describes symptoms not in the visit context, say: 'This sounds like something to discuss with your doctor'"
- "Always cite which part of the visit record your answer comes from"

This took 4 prompt revisions to get right. Each version was tested against a set of edge-case questions:
- "Should I take an extra pill?" → Must refuse
- "What does PVC mean?" → Must explain
- "I'm having chest pain right now" → Must escalate
- "Why did the doctor prescribe propranolol?" → Must answer from visit context

### The Demo Data Problem

Real demo needs real clinical data. Fake data is immediately obvious to anyone with medical knowledge (and hackathon judges may include physicians).

**Iteration 1:** Generated SOAP note with AI. Result: technically correct but felt artificial — no clinical personality, too perfect.

**Iteration 2:** Physician (team member) wrote the transcript and discharge notes from real clinical experience. 40+ minute realistic dialog between cardiologist and patient discussing PVCs. Includes:
- Natural conversation flow (patient interruptions, doctor explanations)
- Real clinical reasoning out loud
- Specific medication discussion (propranolol mechanism, side effects, when to call)
- Lifestyle counseling (caffeine, sleep, stress management)
- Follow-up planning (Holter monitor in 3 months, BP check in 2 weeks)

**Iteration 3:** SOAP note written to match the transcript — as a real doctor would write after the visit. Includes HPI, ROS, physical exam, assessment, and 5-point plan.

**Iteration 4:** Lab results (cholesterol, potassium, TSH), EKG findings, echocardiogram data — all clinically consistent with the PVC scenario. This level of consistency is what makes the demo credible.

---

## Technical Depth: Decisions That Required Thinking

### Why PostgreSQL, Not MySQL
Not a default choice — deliberate. Healthcare data has:
- `specialty_data` that varies by visit type (cardiology EKG intervals ≠ orthopedic range of motion) → **jsonb** with GIN indexing
- Transcripts that need full-text search → **tsvector** with proper stemming
- Every entity needs unique identifiers → native **UUID** type (not varchar(36))
- Audit logs that grow fast → **table partitioning** by month
- Industry standard for HIPAA-compliant systems

### Why Integrated SPA, Not Separate Frontend
Decision #19 was debated:
- Separate repo means CORS configuration, token management, deployment complexity
- Integrated means same-origin cookies (Sanctum), one deploy, one test suite
- For hackathon: integrated is faster. For production: API is still standalone at `/api/v1/`

### Why SSE, Not WebSockets
AI responses stream token by token. Options:
- WebSockets: bidirectional, but complex setup (Pusher/Soketi/Laravel Echo)
- SSE: unidirectional (server→client), native browser support, works with standard HTTP
- For AI streaming: SSE is the right tool. Response flows one direction. No library needed.

### Why Prompts as Files, Not Database
System prompts live in `prompts/` directory, versioned with git:
- `qa-assistant.md` — patient Q&A behavioral rules
- `escalation-detector.md` — emergency detection criteria
- `scribe-processor.md` — transcript → SOAP note processing
- `medical-explainer.md` — term explanation format
- `meds-analyzer.md` — medication analysis instructions
- `visit-structurer.md` — visit data structuring
- `visit-summarizer.md` — patient-friendly summary generation
- `context-guidelines.md` — clinical context formatting

Each prompt is a carefully written document — not a one-liner. The QA assistant prompt alone is 100+ lines with behavioral rules, escalation protocols, source attribution format, and medical disclaimers. These are reviewed like code, not treated as configuration.

---

## Lessons Learned (documented in real-time)

Every mistake was logged immediately in `docs/lessons.md`:

1. **Don't copy stack assumptions between projects** — Initially copied "MySQL, Redis, PHP 8.2" from a sibling project. PostgreSQL was the right choice for healthcare.
2. **Enum values must match between migration and controller** — Validation rules diverged from migration enum. Migration is source of truth.
3. **Auto-generate required identifiers** — FHIR IDs (fhir_encounter_id, etc.) must be auto-generated, not expected from the client.
4. **Sanctum TransientToken has no delete()** — Cookie-based auth uses sessions, not tokens. Must handle both auth modes.
5. **Present work in smaller chunks** — User can't review a wall of text. Max 1 table or 5-8 points at a time.
6. **Always show progress context** — "Section 4" means nothing. "Section 4 of 11" gives context.

These aren't just logged — recurring patterns get promoted into CLAUDE.md as permanent instructions. The system literally learns from its mistakes across sessions.

---

## Security Thinking (Not Just a Checkbox)

Conducted a full OWASP Top 10 security audit (`docs/security-audit.md`):
- Broken Access Control → identified IDOR risks, planned Laravel Policies
- Cryptographic Failures → PHI encryption at rest needed
- Injection → all queries parameterized (Eloquent), no v-html in Vue
- SSRF → documented API proxy risks in medication clients
- Security Misconfiguration → CORS lockdown, demo controller production guard

This isn't "we added auth" — it's "we systematically evaluated every OWASP category against our healthcare context and documented specific remediations."

---

## What "Keep Thinking" Means to Us

This project wasn't built by saying "make me a health app." It was built by:

1. **Understanding the clinical process first** — 4 hours of research before writing code
2. **Documenting every decision** — 26 architectural decisions with rationale
3. **Iterating on the hard problems** — context assembly went through 4 versions, escalation detection through 3, prompt engineering through 4+ revisions per prompt
4. **Learning from mistakes in real-time** — 8 lessons logged and immediately applied
5. **Going deep on data sources** — 9 drug databases researched, 7+ clinical guideline sources evaluated, 6 STT providers compared
6. **Thinking about compliance seriously** — HIPAA, GDPR, SOC 2 analysis with specific architectural implications
7. **Testing with a physician's eye** — demo data written by a real doctor, not generated by AI

The iteration count isn't vanity — it's evidence that we didn't accept the first answer. We kept thinking until each component was right.

---

*Last updated: 2026-02-11 | 47 commits | 15 research documents | 26 architecture decisions | 8 AI prompts | 10 AI services | 67 tests*

### Iteration 8: Clinical Guidelines Licensing Analysis and 3-Layer Architecture (Feb 11)
**What changed:** Designed a compliance-first architecture for integrating clinical guidelines after discovering major licensing restrictions.
**Why:** We investigated using ESC and AHA/ACC guidelines as the AI's "source of truth." ESC has invoked EU Directive 2019/790 Article 4(3) opt-out, explicitly prohibiting AI/LLM use. AHA/ACC holds full copyright with no structured data or redistribution rights. NICE requires written permission for AI use. This forced us to design around these restrictions.
**Before → After:** Before: planned to bundle ESC/AHA guideline PDFs directly. After: 3-layer architecture — (1) Bundled open-licensed sources (WikiDoc CC-BY-SA 3.0, DailyMed public domain, CDC public domain), (2) Runtime RAG via PMC BioC API for AHA/ACC guideline full text (fetched at runtime, cached 24h, never stored in repo), (3) Our own derivative clinical summaries in prompts/guidelines/. This approach is fully compliant while still giving the AI access to high-quality clinical knowledge.
