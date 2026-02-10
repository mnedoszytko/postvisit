# PostVisit.ai

**AI-powered post-visit care platform** that bridges the gap between what happens in the doctor's office and what happens after. Built for the [Claude AI Hackathon](https://anthropic.com) (Feb 10-16, 2026).

## The Problem

After a medical visit, patients forget recommendations, struggle with medical terminology, and have no way to reference what was discussed. Doctors repeat the same explanations and lack feedback on patient understanding. PostVisit.ai solves this by maintaining the full context of a clinical visit and helping the patient afterward.

## What It Does

- **Companion Scribe** - Patient-initiated visit recording ("reverse scribe") that captures the full conversation
- **AI Visit Summary** - Transcription is processed into structured SOAP notes, observations, diagnoses, and prescriptions
- **Contextual Q&A** - Patient asks questions in natural language, gets answers grounded in their visit data + clinical guidelines
- **Medication Intelligence** - Drug interaction checks, dosage explanations, and side effect information via RxNorm
- **Doctor Feedback Loop** - Patients can send follow-up questions and symptoms; doctors receive alerts for concerning patterns
- **Doctor Dashboard** - Practitioners monitor patient engagement, review AI chat transcripts, and respond to escalations

## Architecture

```
PostVisit.ai
├── Laravel 12 (PHP 8.4)          # API + backend logic
├── Vue 3 + Tailwind CSS           # Patient & Doctor SPA
├── Claude Opus 4.6                # AI engine (via Anthropic SDK)
├── PostgreSQL 17                  # Primary database (UUID, jsonb)
└── Laravel Sanctum                # Cookie + token auth
```

**Key design decisions:**
- Integrated Laravel + Vue (no CORS, shared auth) for hackathon speed
- UUID primary keys on all tables for FHIR compatibility
- FHIR-aligned data model (Patient, Encounter, Observation, Condition, MedicationRequest)
- AI prompts versioned as files in `prompts/` directory
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
| Guidelines | ESC Clinical Guidelines | CC-BY |

Full license tracking: [`docs/licenses.md`](docs/licenses.md)

## Quick Start

### Prerequisites
- PHP 8.4, Composer
- PostgreSQL 17
- Bun (or npm)
- Laravel Herd (recommended) or any local PHP server

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

# Database
php artisan migrate
php artisan db:seed --class=DemoSeeder

# Build frontend
bun run build
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
# Start demo session (no auth required)
curl -X POST http://postvisit.test/api/v1/demo/start

# Or login directly
curl -X POST http://postvisit.test/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"patient@demo.postvisit.ai","password":"password"}'
```

## API

45 REST endpoints under `/api/v1/`. Key modules:

| Module | Endpoints | Description |
|--------|-----------|-------------|
| Auth | 4 | Register, login, logout, profile |
| Patients | 8 | Profile, visits, conditions, documents, prescriptions |
| Visits | 3 | Create, view, summary |
| Transcripts | 4 | Upload, view, process, status |
| Chat | 2 | Send message (AI), history |
| Medications | 3 | Search, detail, interactions |
| Feedback | 3 | Patient messages, doctor replies |
| Doctor | 9 | Dashboard, patients, engagement, audit, notifications |
| Audit | 1 | HIPAA-compliant audit logs |
| Demo | 4 | Quick-start, status, reset, simulate alerts |

Full API documentation: [`docs/api.md`](docs/api.md)

## Testing

```bash
php artisan test
```

67 feature tests covering all API modules with 175 assertions. Tests use SQLite in-memory for speed.

## Project Structure

```
app/
├── Http/Controllers/Api/     # 13 API controllers
├── Http/Middleware/           # RoleMiddleware (doctor/admin guards)
├── Models/                   # 18 Eloquent models (UUID, FHIR-aligned)
├── Services/AI/              # AI service layer (prompts, context, streaming)
└── Services/Medications/     # RxNorm integration
database/
├── factories/                # 18 model factories for testing
├── migrations/               # 22 migrations (PostgreSQL-optimized)
└── seeders/                  # DemoSeeder (cardiology scenario)
resources/js/
├── views/                    # 11 Vue views (patient + doctor)
├── components/               # Reusable components (ChatPanel, VisitSection)
├── stores/                   # Pinia stores (auth, visit, chat, doctor)
├── composables/              # useApi (Axios + CSRF), useSse
└── router/                   # Vue Router with role-based guards
docs/                         # Project documentation
prompts/                      # AI system prompts (versioned)
demo/                         # Demo data and scenarios
```

## Documentation

- [`docs/prd.md`](docs/prd.md) - Product Requirements Document
- [`docs/data-model.md`](docs/data-model.md) - Database schema and relationships
- [`docs/api.md`](docs/api.md) - API endpoint reference
- [`docs/decisions.md`](docs/decisions.md) - Architecture decision log
- [`docs/licenses.md`](docs/licenses.md) - Dependency license tracker
- [`docs/lessons.md`](docs/lessons.md) - Development lessons learned

## Hackathon Tracks

PostVisit.ai addresses all three hackathon tracks:

1. **Build a Tool That Should Exist** - No existing system combines visit context, health records, and clinical guidelines for patient-initiated post-visit support
2. **Break the Barriers** - AI translates expert medical knowledge into accessible language for any patient
3. **Amplify Human Judgment** - Doctor-in-the-loop design: the system helps patients understand clinical decisions, never replaces them

## License

MIT License. See [LICENSE](LICENSE) for details.

## Security

See [SECURITY.md](SECURITY.md) for security policies and responsible disclosure.
