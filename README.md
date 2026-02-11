# PostVisit.ai

[![Tests](https://img.shields.io/badge/tests-89%20passed-brightgreen)]()
[![PHP](https://img.shields.io/badge/PHP-8.4-8892BF)]()
[![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20)]()
[![Vue](https://img.shields.io/badge/Vue-3-4FC08D)]()
[![License](https://img.shields.io/badge/license-MIT-blue)](LICENSE)

**AI-powered post-visit care platform** that bridges the gap between what happens in the doctor's office and what happens after. Built for the [Claude AI Hackathon](https://anthropic.com) (Feb 10-16, 2026).

## The Problem

After a medical visit, patients forget up to 80% of what their doctor said. They struggle with medical terminology, have no way to reference what was discussed, and are left with confusing discharge papers. Doctors repeat the same explanations and lack feedback on patient understanding.

PostVisit.ai solves this by maintaining the full context of a clinical visit and providing an AI assistant that helps the patient afterward -- grounded in their actual visit data, not generic search results.

## What It Does

- **Companion Scribe** -- Patient-initiated visit recording with doctor selection, date picker, and hardened 3-phase upload pipeline (save audio, transcribe, combine). Supports long recordings via automatic 10-minute chunking.
- **AI Visit Summary** -- Transcription processed into structured SOAP notes, observations, diagnoses, and prescriptions
- **Contextual Q&A** -- Patient asks questions in natural language; gets answers grounded in their visit data, clinical guidelines, and FDA safety data
- **Medication Intelligence** -- Drug interaction checks, dosage explanations, side effect data from OpenFDA FAERS, and official drug labels from DailyMed
- **Medical Term Explain** -- Click any medical term to get a plain-language explanation tailored to the patient's specific visit context
- **Escalation Detection** -- AI monitors for urgent symptoms (chest pain, breathing difficulty, suicidal ideation) and redirects to emergency services
- **Doctor Feedback Loop** -- Patients send follow-up questions; doctors receive alerts for concerning patterns
- **Doctor Dashboard** -- Practitioners monitor patient engagement, review AI chat transcripts, and respond to escalations

## Architecture

```
PostVisit.ai
+-- Laravel 12 (PHP 8.4)              API + backend logic
+-- Vue 3 + Tailwind CSS               Patient & Doctor SPA
+-- Claude Opus 4.6                    AI engine (streaming SSE)
+-- PostgreSQL 17                      Primary database (UUID, jsonb)
+-- OpenFDA + DailyMed + RxNorm        Drug safety data (public domain)
+-- NIH Clinical Tables               Condition/procedure lookup
+-- Laravel Sanctum                    Cookie + token auth
```

**Key design decisions:**
- Integrated Laravel + Vue (no CORS, shared auth) for hackathon speed
- UUID primary keys on all tables for FHIR compatibility
- FHIR-aligned data model (Patient, Encounter, Observation, Condition, MedicationRequest)
- Server-Sent Events (SSE) for real-time AI streaming
- AI prompts versioned as files in `prompts/` directory
- 5-layer AI context assembly: visit data, patient record, clinical guidelines, medications, FDA safety data
- All patient data is patient-owned (consent model, right to erasure)

## Tech Stack

| Component | Technology | License |
|-----------|-----------|---------|
| Backend | Laravel 12, PHP 8.4 | MIT |
| Frontend | Vue 3, Vue Router, Pinia | MIT |
| AI | Claude Opus 4.6 (Anthropic SDK) | Proprietary API |
| Database | PostgreSQL 17 | PostgreSQL License |
| CSS | Tailwind CSS | MIT |
| Auth | Laravel Sanctum | MIT |
| Drug Data | RxNorm (NLM) | Public Domain |
| Drug Safety | OpenFDA FAERS | Public Domain |
| Drug Labels | DailyMed (NLM) | Public Domain |
| Clinical Tables | NIH Clinical Tables | Public |
| Guidelines | ESC Clinical Guidelines | CC-BY |

Full license tracking: [`docs/licenses.md`](docs/licenses.md)

## Quick Start

### Prerequisites
- PHP 8.4, Composer
- PostgreSQL 17
- Bun (or npm)
- Laravel Herd (recommended) or any local PHP server
- Anthropic API key

### Setup

```bash
# Clone and install
git clone <repo-url> postvisit && cd postvisit
composer install
bun install

# Configure environment
cp .env.example .env
php artisan key:generate
# Edit .env: set DB_CONNECTION=pgsql, DB_DATABASE=postvisit
# Edit .env: set ANTHROPIC_API_KEY=sk-ant-...

# Database
php artisan migrate
php artisan db:seed --class=DemoSeeder

# Build frontend
bun run build
```

### Docker Setup

```bash
docker compose up -d
docker compose exec app php artisan migrate --seed
```

### Demo Accounts

After seeding, two accounts are available:

| Role | Email | Password |
|------|-------|----------|
| Patient | patient@demo.postvisit.ai | password |
| Doctor | doctor@demo.postvisit.ai | password |

Demo scenario: Cardiology visit for PVCs (premature ventricular contractions), prescribed Propranolol 40mg BID.

### API Quick Test

```bash
# Start demo session (no auth required in local env)
curl -X POST http://postvisit.test/api/v1/demo/start

# Or login directly
curl -X POST http://postvisit.test/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"patient@demo.postvisit.ai","password":"password"}'
```

## API

51 REST endpoints under `/api/v1/`. Key modules:

| Module | Endpoints | Description |
|--------|-----------|-------------|
| Auth | 4 | Register, login, logout, profile |
| Patients | 8 | Profile, visits, conditions, documents, prescriptions |
| Practitioners | 1 | List practitioners for visit form |
| Visits | 3 | Create, view, summary |
| Transcripts | 7 | Upload text, upload audio, save chunk, transcribe chunk, view, process, status |
| Chat (SSE) | 2 | AI Q&A with streaming, history |
| Explain (SSE) | 1 | Medical term explanation with streaming |
| Medications | 5 | Search, detail, interactions, adverse events, labels |
| Feedback | 3 | Patient messages, doctor replies |
| Doctor | 9 | Dashboard, patients, engagement, audit, notifications |
| Audit | 1 | HIPAA-compliant audit logs |
| Demo | 4 | Quick-start, status, reset, simulate alerts |

## AI Architecture

PostVisit.ai uses a 5-layer context assembly pattern to give Claude full visit context:

1. **Visit Data** -- SOAP note, observations, test results, transcript
2. **Patient Record** -- Demographics, conditions, active prescriptions
3. **Clinical Guidelines** -- Specialty-specific guidelines for the visit type
4. **Medications** -- Drug details, dosing, interactions from RxNorm
5. **FDA Safety Data** -- Adverse event reports (FAERS) and official drug labels from OpenFDA

AI services include:
- **QaAssistant** -- Streaming Q&A with escalation detection
- **MedicalExplainer** -- Term-level explanations in patient context
- **MedsAnalyzer** -- Drug analysis with interaction checks
- **EscalationDetector** -- Keyword + AI urgency evaluation
- **ContextAssembler** -- Layered context builder

## Testing

```bash
php artisan test
```

89 feature tests covering all API modules with 264 assertions. Tests use SQLite in-memory for speed, with mocked AI services.

## Project Structure

```
app/
+-- Http/Controllers/Api/     # 13 API controllers
+-- Http/Middleware/           # RoleMiddleware (doctor/admin guards)
+-- Models/                   # 18 Eloquent models (UUID, FHIR-aligned)
+-- Services/AI/              # AI service layer (prompts, context, streaming)
+-- Services/Medications/     # RxNorm, OpenFDA, DailyMed, NIH clients
database/
+-- factories/                # 18 model factories for testing
+-- migrations/               # 22 migrations (PostgreSQL-optimized)
+-- seeders/                  # DemoSeeder (cardiology scenario)
resources/js/
+-- views/                    # 11 Vue views (patient + doctor)
+-- components/               # ChatPanel, VisitSection, ToastContainer
+-- stores/                   # Pinia stores (auth, visit, chat, doctor, toast)
+-- composables/              # useApi (Axios + CSRF)
+-- router/                   # Vue Router with role-based guards
docs/                         # Project documentation
prompts/                      # AI system prompts (versioned)
demo/                         # Demo data and scenarios
```

## Documentation

- [`docs/prd.md`](docs/prd.md) -- Product Requirements Document
- [`docs/data-model.md`](docs/data-model.md) -- Database schema and relationships
- [`docs/decisions.md`](docs/decisions.md) -- Architecture decision log
- [`docs/licenses.md`](docs/licenses.md) -- Dependency license tracker
- [`docs/security-audit.md`](docs/security-audit.md) -- OWASP Top 10 security audit
- [`docs/lessons.md`](docs/lessons.md) -- Development lessons learned

## Hackathon Tracks

PostVisit.ai addresses all three hackathon tracks:

1. **Build a Tool That Should Exist** -- No existing system combines visit context, health records, and clinical guidelines for patient-initiated post-visit support
2. **Break the Barriers** -- AI translates expert medical knowledge into accessible language for any patient, regardless of health literacy
3. **Amplify Human Judgment** -- Doctor-in-the-loop design: the system helps patients understand clinical decisions, never replaces them

## Disclaimer

All clinical scenarios, patient profiles, medical records, and health data displayed in this application are entirely fictional, created for demonstration purposes only, and do not depict any real person or actual medical encounter.

## License

MIT License. See [LICENSE](LICENSE) for details.

## Security

See [SECURITY.md](SECURITY.md) for security policies and responsible disclosure.
