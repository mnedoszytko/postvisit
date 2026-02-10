# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**PostVisit.ai** — system AI utrzymujący kontekst konkretnej wizyty klinicznej, pomagający pacjentowi po wyjściu z gabinetu. Hackathon project: Built with Opus 4.6 (10-16 luty 2026).

Pełna dokumentacja projektu: `docs/seed.md`
Log decyzji: `docs/decisions.md`
Log błędów i poprawek: `docs/lessons.md`
Tracker licencji i compliance: `docs/licenses.md`

## Continuous Improvement Process

When the user corrects a mistake, **immediately** log it in `docs/lessons.md` with: what went wrong, the correction, and the takeaway. Every 3-5 iterations, review `docs/lessons.md` and promote recurring patterns into this CLAUDE.md file.

## Stack

- **Backend**: Laravel 12, PHP 8.4
- **Frontend**: Vue 3 (integrated in `resources/js/`), Vite
- **CSS**: Tailwind CSS v4
- **Package manager**: Bun
- **AI**: Claude Opus 4.6 (Anthropic PHP SDK — `anthropic-ai/laravel`)
- **Auth**: Laravel Sanctum (cookie-based SPA)
- **Hosting**: Laravel Forge + Hetzner
- **Development**: Laravel Herd (local, PHP 8.4 isolated)
- **Database**: PostgreSQL 17 (Herd) — jsonb, tsvector, native UUID
- **Cache**: Database driver (PostgreSQL)
- **Queue**: Database driver (PostgreSQL)

## Essential Commands

### Development
- Never run `bun run dev` or `npm run dev` — dev server already runs in background via Herd
- Never run `php artisan serve` — Herd handles this automatically
- Local URL: `postvisit.test` (Herd convention based on directory name)
- Use `herd php artisan` for all artisan commands (ensures PHP 8.4 isolated version)

### Backend
- `herd php artisan test` — run all tests
- `herd php artisan test --filter=ClassName` — run single test class
- `herd php artisan migrate` — run migrations
- `./vendor/bin/pint` — fix PHP code style (Laravel Pint)

### Frontend
- `bun run build` — build for production
- `bun add <package>` — add dependency (NOT npm)

## Architecture

```
postvisit/
├── app/
│   ├── Http/Controllers/Api/   # API controllers
│   ├── Http/Middleware/         # RoleMiddleware, AuditMiddleware
│   ├── Http/Requests/          # Form Request validation
│   ├── Http/Resources/         # API Resources (JSON transformers)
│   ├── Models/                 # Eloquent models (18, all HasUuids)
│   └── Services/
│       ├── AI/                 # AI service layer (10 classes)
│       ├── Medications/        # RxNorm client
│       └── Stt/                # STT adapter (Whisper)
├── resources/
│   ├── js/
│   │   ├── views/              # Vue views (11 screens)
│   │   ├── components/         # Reusable Vue components
│   │   ├── stores/             # Pinia stores (auth, visit, chat, doctor)
│   │   ├── router/             # Vue Router
│   │   ├── composables/        # useApi, useSse, useAuth
│   │   └── layouts/            # PatientLayout, DoctorLayout
│   └── css/                    # Tailwind CSS
├── prompts/                    # AI system prompts — versioned like code
├── demo/                       # Demo data, mocks, guidelines
├── docs/                       # Working documentation
├── routes/
│   ├── api.php                 # /api/v1/ endpoints (~40)
│   └── web.php                 # SPA catch-all
└── database/
    ├── migrations/             # 22 migration files
    └── seeders/                # DemoSeeder
```

### Prompts as Code
System prompts in `prompts/` are versioned and reviewable. Never hardcode prompts in controllers — import from files via `PromptLoader` service.

### Key Architecture Patterns
- **Integrated SPA**: Vue runs inside Laravel (resources/js/), not a separate repo. Zero CORS issues.
- **Sanctum cookie auth**: Same-origin, stateful sessions. No tokens in localStorage.
- **SSE streaming**: AI responses stream via Server-Sent Events (`/chat`, `/explain` endpoints).
- **Service layer**: All AI logic in `app/Services/AI/`, business logic never in controllers.
- **UUID everywhere**: All models use `HasUuids` trait, PostgreSQL native uuid type.

## Coding Guidelines

### Language Policy
- **Official project language: English.** All code, variable names, comments, commit messages, UI text, and repository documentation (README, SECURITY, etc.) MUST be in English.
- **Working docs in `docs/`** may be in Polish during development — they will be translated before final submission.
- **User prompts in chat** come in Polish and English — both are normal, always respond in the same language the user used.
- **Never translate or "correct" user's prompts** — the bilingual workflow is intentional and more effective.

### General Rules
- NEVER hardcode data to resolve a problem — only if explicitly instructed by user
- Never Co-Author commits with Claude Code as author

### Hackathon Compliance (CRITICAL)
When adding ANY new dependency, data source, or external service:
1. Check its license — must be open source or public domain for bundled components
2. Update `docs/licenses.md` with: name, license, role, status
3. If proprietary API (not bundled): mark as "external service" — OK if our integration code is open source
4. If data bundled in repo: ONLY CC-BY, CC-BY-SA, or public domain. NO CC-BY-NC, NO proprietary.
5. Demo video must show ONLY open source components
6. When in doubt — ASK before adding

### Architecture Decision Escalation
Gdy napotkasz fork in the road — ZATRZYMAJ SIĘ i eskaluj:

```
DECYZJA ARCHITEKTONICZNA WYMAGANA
Zadanie: [opis]
Opcja A: [nazwa] — Zalety / Wady
Opcja B: [nazwa] — Zalety / Wady
Rekomendacja: [A/B] — [powód]
```

### Requirements Clarification
Dla feature'ów dotyczących danych medycznych, promptów AI, lub flow pacjenta — zawsze pytaj o wymagania PRZED implementacją.

## Model Policy

- **Production / demo**: Claude Opus 4.6
- **Tests / development**: Sonnet is OK (cost optimization)
- **Subagents (Task tool)**: always use `model: 'opus'`
- **Agent Teams**: always use `model: 'opus'` — NEVER haiku or sonnet for teammates

- Hackathon deadline: **16 lutego 2026, 15:00 EST** — priorytet to działający prototyp, nie perfekcyjna dokumentacja

## Linear (Project Management)

Team **POST** in the `medduties` workspace. API key is available as `$LINEAR_API_KEY` env var.

### Querying issues via GraphQL

```bash
curl -s -X POST 'https://api.linear.app/graphql' \
  -H 'Content-Type: application/json' \
  -H "Authorization: $LINEAR_API_KEY" \
  -d '{"query":"{ team(id: \"506cce46-72bf-4ee9-80d6-754659668b7b\") { issues(first: 50) { nodes { identifier title state { name } priority priorityLabel assignee { name } } } } }"}'
```

### Creating / updating issues

```bash
# Create an issue
curl -s -X POST 'https://api.linear.app/graphql' \
  -H 'Content-Type: application/json' \
  -H "Authorization: $LINEAR_API_KEY" \
  -d '{"query":"mutation { issueCreate(input: { teamId: \"506cce46-72bf-4ee9-80d6-754659668b7b\", title: \"Issue title\", description: \"Details\" }) { success issue { identifier url } } }"}'

# Update issue state (use stateId from workflow states query)
curl -s -X POST 'https://api.linear.app/graphql' \
  -H 'Content-Type: application/json' \
  -H "Authorization: $LINEAR_API_KEY" \
  -d '{"query":"mutation { issueUpdate(id: \"<issue-uuid>\", input: { stateId: \"<state-uuid>\" }) { success } }"}'
```

### Key IDs
- **Team POST**: `506cce46-72bf-4ee9-80d6-754659668b7b`
- Use `$LINEAR_API_KEY` env var — never hardcode the token

### Tips
- Always use GraphQL API (`https://api.linear.app/graphql`) — do not rely on browser automation for Linear
- Pipe output through `python3 -m json.tool` for readable formatting
- Linear API docs: https://developers.linear.app/docs/graphql/working-with-the-graphql-api

## TODO — PRZYPOMNIENIA DLA NEDO

### BLOKUJĄCE (bez tego demo nie ruszy)
- [ ] **Transkrypt wizyty** — Nedo pisze w gabinecie (11 luty). Realistyczny dialog kardiolog-pacjent. Scenariusz: PVCs, propranolol 40mg 2x/day. Format: tekst. Zapisz w `demo/transcript.txt`.
- [ ] **Wypis lekarski / discharge notes** — Nedo pisze w gabinecie (11 luty). Prawdziwy format wypisu. Zapisz w `demo/discharge-notes.txt`.

### Do wygenerowania (Claude zrobi)
- [ ] Mock lab results (cholesterol, K+, TSH) — JSON
- [ ] Mock Apple Watch data (HR, PVC events) — JSON
- [ ] Mock doctor dashboard data (patient list, alerts) — JSON
