# PostVisit.ai — Architecture & Scaffolding Plan

## Context

PostVisit.ai is a healthcare AI hackathon project (deadline: Feb 16, 15:00 EST). The repo has comprehensive documentation (PRD, data model, research docs) but zero code. This plan scaffolds the entire application — Laravel 12, Vue 3, PostgreSQL, AI integration — so we can start building features immediately.

**Architecture change from PRD:** Originally planned separate `backend/` + `frontend/` directories. Decision changed to **integrated Laravel + Vue** (Vue in `resources/js/`) for zero CORS, simpler auth, and faster hackathon development. API remains standalone and fully accessible.

## Decided Stack

| Component | Choice |
|-----------|--------|
| Backend | Laravel 12, PHP 8.4 |
| Frontend | Vue 3 in `resources/js/` (integrated) |
| CSS | Tailwind CSS |
| Package manager | Bun |
| Database | PostgreSQL 17 (Herd) |
| Auth | Laravel Sanctum (cookie-based SPA) |
| AI | Claude Opus 4.6 (Anthropic PHP SDK) |
| Local dev | Laravel Herd |
| Bundler | Vite (Laravel default) |

## Execution Plan

Execution is **step-by-step**. Each phase is verified before moving to the next.

### Phase 0: Prerequisites
1. Verify Herd is on PHP 8.4 (may need GUI switch)
2. Install Bun (`curl -fsSL https://bun.sh/install | bash`)
3. Create PostgreSQL database: `CREATE DATABASE postvisit;`
4. Create PostgreSQL user if needed
5. **Verify:** `php -v` shows 8.4, `bun --version` works, `psql -l` shows postvisit DB

### Phase 1: Laravel Installation
1. Create fresh Laravel 12 in temp dir (`composer create-project laravel/laravel /tmp/postvisit-temp`)
2. Copy Laravel files into existing repo (preserve docs/, CLAUDE.md, LICENSE, SECURITY.md, .gitignore)
3. `composer install`
4. Configure `.env` with PostgreSQL (`DB_CONNECTION=pgsql`, port 5432)
5. `php artisan key:generate`
6. Clean up temp dir
7. **Verify:** `postvisit.test` serves Laravel welcome page via Herd

### Phase 2: Frontend Setup
1. Remove `package-lock.json`, run `bun install`
2. `bun add vue@3 vue-router@4 pinia @vitejs/plugin-vue`
3. Configure Tailwind CSS (Laravel 12 may include it)
4. `bun add axios`
5. Configure `vite.config.js` with Vue plugin + `@` alias
6. **Verify:** `bun run build` completes without errors

### Phase 3: Directory Structure
Create directories for the full application:
```
app/Http/Controllers/Api/     — 11 API controllers
app/Http/Middleware/           — AuditMiddleware, RoleMiddleware
app/Http/Requests/             — Form Request validation
app/Http/Resources/            — API Resources (JSON transformers)
app/Services/AI/               — 9 AI service classes
app/Services/Medications/      — RxNorm client
app/Services/Stt/              — STT adapter
app/Models/                    — 18 Eloquent models
resources/js/views/            — 11 Vue views (from PRD S1-S11)
resources/js/components/       — Reusable Vue components
resources/js/stores/           — Pinia stores (auth, visit, chat, doctor)
resources/js/router/           — Vue Router config
resources/js/composables/      — useApi, useSse, useAuth
resources/js/layouts/          — PatientLayout, DoctorLayout
prompts/                       — 8 AI system prompt files
demo/                          — Demo data (transcript, discharge, mocks)
demo/guidelines/               — ESC cardiology guideline (CC-BY)
demo/mock/                     — Generated mock data (labs, watch, dashboard)
```
**Verify:** directories exist, `git status` shows new dirs

### Phase 4: Database Migrations (22 files)
All tables use UUID primary keys. Order respects FK dependencies:

1. `organizations` — no FKs
2. `patients` — soft deletes, consent fields, `created_by` deferred
3. `practitioners` — FK: organizations
4. `users` — **replaces default Laravel migration**. `role` enum (patient|doctor|admin), FKs: patients, practitioners
5. `add_created_by_to_patients` — deferred FK to users
6. `visits` — FKs: patients, practitioners, organizations, users
7. `observations` — FKs: patients, visits, practitioners. `specialty_data` jsonb
8. `conditions` — FKs: patients, visits. ICD-10/SNOMED codes
9. `medications` — standalone drug master data. `rxnorm_code` unique
10. `prescriptions` — FKs: patients, practitioners, visits, medications
11. `medication_interactions` — FKs: medications (self-ref). UNIQUE(drug_a_id, drug_b_id)
12. `documents` — FKs: patients, visits
13. `transcripts` — FKs: visits, patients. `diarized_transcript` jsonb, `soap_note` jsonb
14. `visit_notes` — FKs: visits, patients, practitioners. SOAP sections
15. `chat_sessions` — FKs: patients, visits
16. `chat_messages` — FK: chat_sessions
17. `audit_logs` — FK: users. Composite indices. Immutable.
18. `consents` — FK: patients
19. `notifications` — FKs: users, visits (new table, not in data-model.md)
20. `personal_access_tokens` — Sanctum
21. `cache` — database cache driver
22. `jobs` — database queue driver

**Verify:** `php artisan migrate` succeeds, `\dt` in psql shows all 22 tables

### Phase 5: Eloquent Models (18 models)
All models use `HasUuids` trait. Key relationships per `docs/data-model.md`:
- Visit is the central aggregate (hasMany: observations, conditions, prescriptions, documents; hasOne: transcript, visit_note)
- Patient owns data (hasMany: visits, conditions, consents, documents, chat_sessions)
- Medication acts as RxNorm cache (hasMany: prescriptions, interactions)
- jsonb fields cast as arrays (specialty_data, soap_note, entities_extracted, etc.)

**Verify:** `php artisan tinker` — create and query models, verify relationships

### Phase 6: Backend Dependencies
```
composer require laravel/sanctum        # SPA auth
composer require anthropic-ai/laravel   # Anthropic SDK
```
**Verify:** packages installed, configs published

### Phase 7: API Routes (~40 endpoints)
All in `routes/api.php` under `/api/v1/` prefix. Matching PRD Section 8:
- Auth: register, login, logout, user
- Core Health: patient profile, conditions, health-record, documents
- Companion Scribe: transcript upload, process, status
- PostVisit AI: visit view, explain (SSE), chat (SSE), history
- Meds: search (RxNorm proxy), detail, interactions, prescriptions
- Feedback: messages, mark read
- Doctor: dashboard, patients, engagement, chat-audit, notifications, reply
- Audit: logs (doctor/admin only)
- Demo: start, status, reset, simulate-alert

SPA catch-all in `routes/web.php` → serves Vue app.

**Verify:** `php artisan route:list` shows all endpoints

### Phase 8: Service Layer
**AI Services** (`app/Services/AI/`):
- `PromptLoader` — loads markdown prompts from `prompts/`
- `AnthropicClient` — wraps SDK, supports SSE streaming
- `ContextAssembler` — builds layered context per PRD Section 6
- 7 subsystem services: ScribeProcessor, VisitStructurer, QaAssistant, MedicalExplainer, MedsAnalyzer, EscalationDetector, VisitSummarizer

**RxNorm** (`app/Services/Medications/RxNormClient.php`):
- Proxy to `rxnav.nlm.nih.gov/REST`
- Caches results in `medications` table

**STT** (`app/Services/Stt/`):
- `SpeechToTextProvider` interface
- `WhisperProvider` implementation
- Adapter pattern, swappable via config

**Verify:** service classes instantiate without errors

### Phase 9: Vue App Structure
**Views** (11 screens from PRD):
Landing, PatientProfile, CompanionScribe, Processing, VisitView, MedsDetail, Feedback, DoctorDashboard, DoctorPatientDetail, DemoMode, Login

**Components**: ChatPanel (slide-in), VisitSection, MedCard, ExplainButton, StreamingMessage, NotificationBanner, LoadingParticles

**Stores** (Pinia): auth, visit, chat, doctor

**Composables**: useApi (Axios + Sanctum), useSse (EventSource for streaming), useAuth (guards)

**Router**: client-side routing with auth guards per role (patient/doctor)

**Verify:** `bun run build` succeeds, `postvisit.test` shows Vue app

### Phase 10: Auth Setup
- Sanctum stateful domains: `postvisit.test`
- CORS: `supports_credentials: true`
- `RoleMiddleware` for doctor/admin route protection
- Cookie-based session auth (same-origin, no CORS issues)

**Verify:** login/logout works, role-based access enforced

### Phase 11: Demo Seeder
Creates full cardiology scenario (PVCs + propranolol):
- Organization: "City Heart Clinic"
- Practitioner: Dr. Nedo (cardiologist)
- Patient: Alex Johnson
- Users: patient@demo.postvisit.ai / doctor@demo.postvisit.ai (password: "password")
- Visit with transcript, SOAP note, observations, conditions, prescriptions
- **BLOCKED on**: Nedo writes transcript + discharge notes (Feb 11 in office)

**Verify:** `php artisan db:seed --class=DemoSeeder` succeeds, data visible in DB

### Phase 12: Prompt Files
8 markdown files in `prompts/` — stubs created, detailed engineering during implementation.

**Verify:** files exist, PromptLoader can load each one

## CLAUDE.md Updates
- Replace `backend/` + `frontend/` structure with standard Laravel
- Replace `npm` commands with `bun`
- Update stack: PostgreSQL (was TBD), Laravel 12 (was just "Laravel")
- Keep "Never run bun run dev" (user runs in background)
- Keep "Never run php artisan serve" (Herd)

## End-to-End Verification
1. `postvisit.test` serves the Vue SPA
2. `php artisan migrate` creates all 22 tables in PostgreSQL
3. `php artisan db:seed --class=DemoSeeder` loads demo data
4. `POST /api/v1/auth/login` returns Sanctum session
5. `GET /api/v1/visits/{id}` returns structured visit data
6. `POST /api/v1/visits/{id}/chat` streams AI response via SSE

## Critical Source Files
- `docs/data-model.md` — source of truth for all migrations (field definitions, types, FKs, indices)
- `docs/prd.md` — API surface (Section 8), AI architecture (Section 6), screens (Section 5)
- `docs/decisions.md` — resolved decisions (roles as enum, RxNorm cache, REST for demo)
- `docs/stt-scribing.md` — STT adapter pattern architecture
