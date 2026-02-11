# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**PostVisit.ai** — system AI utrzymujący kontekst konkretnej wizyty klinicznej, pomagający pacjentowi po wyjściu z gabinetu. Hackathon project: Built with Opus 4.6 (10-16 luty 2026).

Pełna dokumentacja projektu: `docs/seed.md`
Log decyzji: `docs/decisions.md`
Log błędów i poprawek (dev/tooling): `docs/lessons.md`
Log iteracji i głębi klinicznej: `docs/KEEP-THINKING.md`
Tracker licencji i compliance: `docs/licenses.md`

## Incremental Documentation (CRITICAL — hackathon judging criterion)

Documentation is a **core evaluation criterion** of this hackathon. Build it incrementally as you code — NOT at the end.

### Rules
1. **After completing any feature, endpoint, or significant change** — immediately update the relevant doc file(s).
2. **README.md** — keep it current: project overview, setup instructions, architecture summary, demo instructions. Update after every major milestone.
3. **docs/api.md** — document every API endpoint as it's implemented: method, path, auth, request/response examples.
4. **docs/architecture.md** — update when architecture changes: diagrams, data flow, service layer, AI pipeline.
5. **docs/ai-prompts.md** — document each AI prompt: purpose, input context, expected output, versioning.
6. **docs/demo-guide.md** — step-by-step demo walkthrough, keep in sync with the actual working demo.
7. **Code comments** — add JSDoc/PHPDoc for non-obvious public methods. Don't over-comment, but document "why" not "what".
8. **CHANGELOG.md** — maintain a human-readable changelog of features added, in reverse chronological order.

### Documentation Quality Standards
- All docs in English (final submission language)
- Include code examples and screenshots where helpful
- Keep docs concise but complete — judges have limited time
- Every doc should be understandable by someone new to the project

## Continuous Improvement Process

### Two logs — different purposes

1. **`docs/lessons.md`** — dev/tooling lessons only. Bugs in code, SDK quirks, migration gotchas, test failures, CLI pitfalls. These are engineering corrections.

2. **`docs/KEEP-THINKING.md`** — project-level iterations and clinical depth. Log here when:
   - You discover a deeper understanding of the clinical process or patient experience
   - An AI prompt is revised because the previous version misunderstood medical nuance
   - A context assembly approach is changed (e.g. "we added transcript as highest-priority context because discharge notes miss conversational nuance")
   - A design decision is reconsidered after learning more about how real clinical workflows operate
   - A guardrail is refined (e.g. "the escalation detector was too aggressive — normal post-visit descriptions were flagged")
   - You iterate on demo data quality (e.g. "AI-generated SOAP note felt artificial, physician rewrote it")
   - A compliance or safety consideration leads to an architectural change
   - You research a new clinical data source, drug database, or guideline set

   **Format for new entries:**
   ```
   ### Iteration N: [Short title] (date)
   **What changed:** [1-2 sentences]
   **Why:** [The clinical/product insight that drove the change]
   **Before → After:** [What was the old approach vs the new one]
   ```

### Routing rule
- If the lesson is about **how to use a tool, fix a bug, or avoid a dev mistake** → `docs/lessons.md`
- If the lesson is about **understanding the problem deeper, improving clinical accuracy, or iterating on product design** → `docs/KEEP-THINKING.md`
- When in doubt: if a hackathon judge would find it interesting, it goes in KEEP-THINKING.

### Promotion
Every 3-5 iterations, review `docs/lessons.md` and promote recurring dev patterns into this CLAUDE.md file. The KEEP-THINKING log is never "promoted" — it grows as a living narrative of the project's depth.

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

## Git Strategy

### Hackathon Mode — Direct to Main (no PRs)
PRs are disabled for hackathon speed. All work goes directly to `main` with discipline.

### Worktree Setup
- `/postvisit` — primary worktree (human + main agent), on `main`
- `/postvisit-agent2` through `/postvisit-agent5` — agent worktrees, each on own branch

Each agent worktree gets its own branch (`agentN-workspace`). **Never delete another agent's worktree** — other Claude Code sessions may be using it.

### Commit Rules
- **Every commit must**: pass `herd php artisan test` AND `bun run build`
- **Never push to `main` if tests fail**
- Never Co-Author commits with Claude Code as author
- Check `storage/logs/laravel.log` for errors after making changes

### Agent Workflow (CRITICAL — prevents merge overwrites)
Agents work on short-lived branches. **Always rebase before merging to main.**

```bash
# 1. Start work — fresh branch from latest main
git fetch origin main
git checkout -b fix/my-task FETCH_HEAD

# 2. Do the work, commit

# 3. Before merging — ALWAYS rebase onto latest main
git fetch origin main
git rebase FETCH_HEAD

# 4. Merge to main (use worktree or merge FETCH_HEAD)
git checkout main       # if main is available
git merge fix/my-task --no-edit
git push origin main

# 5. If main is locked by another worktree:
git fetch origin main
git rebase FETCH_HEAD   # rebase your branch
git push origin fix/my-task
# then merge from the main worktree
```

**Why rebase is mandatory:** Without rebase, merging a branch created from old main can silently overwrite newer changes. Git sees old file versions on the branch and keeps them. Rebase replays your changes on top of current main, making conflicts visible.

### Tags (checkpoints)
Before risky merges, tag current main:
```
git tag pre-<feature>
```
Use for instant rollback if merge breaks something.

### Branch Hygiene
- **Short-lived branches only** — branch, work, merge within minutes/hours, not days
- Delete branches after merge: `git branch -d fix/my-task`
- Never let a branch diverge more than a few hours from main
- If a branch is old, **rebase it** before doing anything: `git fetch origin main && git rebase FETCH_HEAD`

### Multi-Agent Safety
- Max 2 agents working in parallel
- Agents MUST work on separate files when possible (e.g. agent1: backend, agent2: frontend)
- **Always rebase before merge** — this is the single most important rule
- If merge conflict: resolve on feature branch, never force-push main
- Only one agent merges to `main` at a time

### VPS Deployment (when Forge is ready)
- Forge will auto-deploy from `main`
- Before enabling: add `dev` branch as buffer between work and deploy
- Flow becomes: `feature/* → dev → main (deploy)`

## Changelog Policy

Every significant change must be recorded in `CHANGELOG.md`. This is our primary tool for tracing what changed and when.

### Rules
1. **Every meaningful change gets an entry** — new features, bug fixes, refactors, dependency updates, architecture changes. Skip trivial formatting or typo fixes.
2. **Format**: reverse chronological order, grouped by date (YYYY-MM-DD), with sections: Added, Fixed, Changed, Removed as needed.
3. **Keep descriptions short** — one line per change, enough context to understand what and why.
4. **No versioning yet** — we don't tag releases or bump versions until MVP is reached. CHANGELOG.md is purely for tracing changes over time.
5. **Update in the same commit** — when you implement a feature or fix, update CHANGELOG.md in the same commit (or PR). Don't batch changelog updates separately.

### Example entry
```markdown
## 2026-02-11

### Added
- Medical term highlighting with tap-to-explain in SOAP notes

### Fixed
- URL redirect handling during Chrome testing
```

## Coding Guidelines

### Language Policy
- **Official project language: English.** All code, variable names, comments, commit messages, UI text, and repository documentation (README, SECURITY, etc.) MUST be in English.
- **Working docs in `docs/`** may be in Polish during development — they will be translated before final submission.
- **User prompts in chat** come in Polish and English — both are normal, always respond in the same language the user used.
- **Never translate or "correct" user's prompts** — the bilingual workflow is intentional and more effective.

### Pre-Implementation Verification (CRITICAL)
Before writing ANY new code — component, service, endpoint, helper, or utility — you MUST:

1. **Search for existing implementations.** Use Grep/Glob to check if similar functionality already exists in the codebase. Check:
   - `app/Services/` — is there already a service that does this or something close?
   - `app/Http/Controllers/` — is there an endpoint that already handles this?
   - `resources/js/components/` — is there a Vue component that does the same thing?
   - `resources/js/composables/` — is there a composable with this logic?
   - `resources/js/stores/` — is this state already managed somewhere?

2. **Extend, don't duplicate.** If similar code exists:
   - Add a parameter or method to the existing class — don't create a parallel one
   - Extract shared logic into a common function — don't copy-paste
   - If two components do 80% the same thing, refactor to one configurable component

3. **Check for unused code.** Before adding a new approach, verify the old one is removed. Do not leave dead code, orphaned components, or unused imports behind.

4. **Name consistently.** Before naming a new file/class/function, check how siblings are named. Follow the same pattern. If visit-related services are `VisitSummarizer`, `VisitStructurer`, don't name yours `SummarizeVisitService`.

**If you find existing code that overlaps with what you're about to write — STOP and refactor instead of creating a duplicate.** Duplication is a bug.

### Field Audit Rule (CRITICAL — most common bug pattern)
Before referencing ANY model field in a controller, service, or Vue template:
1. **Check the migration** — it's the source of truth for column names, types, enums, and nullability.
2. **Check the model's `$fillable`/`$casts`** — verify the field is accessible and properly cast.
3. **Check the API Resource / controller response** — frontend must use the exact field paths returned by the API, not guessed ones.
4. **For enum columns** — controller validation values MUST match migration enum values exactly.
5. **For NOT NULL columns** — ensure every column has a value set in the controller (auto-generate or default).

Common traps: `patient.name` (doesn't exist — it's `first_name`/`last_name`), `visit.visit_date` (doesn't exist — it's `started_at`), `$transcript->raw_text` (it's `raw_transcript`).

### Ephemeral Data Protection
When handling irreplaceable user data that exists only in browser memory (recordings, file uploads, unsaved forms):
1. **Save to server FIRST, process SECOND** — never combine save+process in one atomic step. If processing fails, the data must already be on disk.
2. **Async API safety** — browser media APIs (MediaRecorder) are event-driven. After `.stop()`, data is NOT ready until `onstop` fires. Always await the completion event.
3. **Closure isolation** — when rotating/replacing async producers (e.g. chunk rotation), each producer must own its data via closures. Never share mutable arrays between async callbacks.
4. **`beforeunload` protection** — any page holding ephemeral data MUST register a `beforeunload` handler during active recording/upload. Remove on unmount.
5. **Idempotent retries** — persist intermediate resource IDs (visit ID, chunk IDs) so retries reuse existing resources instead of creating orphans.

### AI Output Validation
Never trust AI-generated structured data (character offsets, positions, counts, classifications) without programmatic validation:
- Backend: validate every AI output field before storing (e.g. extract substring at claimed offset, compare to claimed term)
- Frontend: validate again client-side, with fallback behavior (e.g. text search if offset is wrong)
- Drop invalid entries with debug logging rather than crashing

### Demo Seeder Completeness
The DemoSeeder must produce **complete, feature-ready data for ALL demo scenarios**, not just the first one:
- Every visit must have all dependent data (visit notes, medical_terms, observations, conditions, prescriptions)
- If a feature depends on AI-generated data (e.g. term extraction), the seeder must invoke that service for all scenarios
- Critical demo data should be hardcoded for reliability; AI services supplement but don't replace deterministic seeding

### Axios Interceptor Safety
Global axios interceptors that perform side effects (navigation, toasts) must have:
1. **Opt-out flags** — e.g. `skipAuthRedirect: true` for requests that expect 401s (session checks on public pages)
2. **Deduplication** — parallel requests triggering the same error (e.g. 3 concurrent 401s) must show ONE toast, not N. Use timestamp-based cooldown (5s)
3. **Scope awareness** — don't redirect to login from pages that don't require auth

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

## Agent Teams Policy

For **medium or large complexity tasks**, always use agent teams (Task tool with `TeamCreate` + multiple teammates). This includes:
- Multi-file features (3+ files)
- Tasks with independent sub-tasks that can run in parallel
- Complex merges, large refactors, multi-endpoint implementations

For **small/simple tasks** (single file edit, quick fix, one endpoint), work directly without spawning a team.

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
- [x] **Transkrypt wizyty** — DONE (`demo/transcript.txt`). Realistyczny dialog kardiolog-pacjent, PVCs, propranolol 40mg 2x/day.
- [x] **Wypis lekarski / discharge notes** — DONE (`demo/discharge-notes.txt`). Pełny format wypisu.

### Nedo — do zrobienia DZIŚ (11 Feb)
- [ ] **Export WAV'ów z Voice Memos** — kilkanaście nagranych scenariuszy klinicznych. Share → Files/AirDrop, format m4a OK (Whisper je przyjmie). Wrzucić do `demo/audio/`.
- [ ] Każdy WAV = osobny case kliniczny → budujemy bibliotekę demo cases

### Do wygenerowania (Claude zrobi)
- [x] Mock lab results (cholesterol, K+, TSH) — DONE (seeded in DemoSeeder as Observations)
- [ ] Mock Apple Watch data (HR, PVC events) — JSON
- [ ] Mock doctor dashboard data (patient list, alerts) — JSON (dashboard endpoint now works with real data)
- [ ] Przetworzyć WAV'y przez Whisper → transkrypty → structured visit summaries (po otrzymaniu plików)

### Dokumentacja (hackathon criterion)
- [x] README.md — project overview, setup, demo guide — DONE
- [x] docs/api.md — full API documentation (45 endpoints) — DONE
- [x] docs/decisions.md — updated with all 26 decisions — DONE
- [x] docs/licenses.md — updated with current deps — DONE
- [ ] docs/architecture.md — system architecture, data flow, AI pipeline
- [ ] docs/demo-guide.md — step-by-step demo walkthrough
- [ ] CHANGELOG.md — feature changelog

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to enhance the user's satisfaction building Laravel applications.

## Foundational Context
This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4.15
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- laravel/pint (PINT) - v1
- vue (VUE) - v3
- tailwindcss (TAILWINDCSS) - v4


## Conventions
- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts
- Do not create verification scripts or tinker when tests cover that functionality and prove it works. Unit and feature tests are more important.

## Application Structure & Architecture
- Stick to existing directory structure - don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling
- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Replies
- Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Documentation Files
- You must only create documentation files if explicitly requested by the user.


=== boost rules ===

## Laravel Boost
- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan
- Use the `list-artisan-commands` tool when you need to call an Artisan command to double check the available parameters.

## URLs
- Whenever you share a project URL with the user you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain / IP, and port.

## Tinker / Debugging
- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool
- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)
- Boost comes with a powerful `search-docs` tool you should use before any other approaches. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation specific for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- The 'search-docs' tool is perfect for all Laravel related packages, including Laravel, Inertia, Livewire, Filament, Tailwind, Pest, Nova, Nightwatch, etc.
- You must use this tool to search for Laravel-ecosystem documentation before falling back to other approaches.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic based queries to start. For example: `['rate limiting', 'routing rate limiting', 'routing']`.
- Do not add package names to queries - package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax
- You can and should pass multiple queries at once. The most relevant results will be returned first.

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit"
3. Quoted Phrases (Exact Position) - query="infinite scroll" - Words must be adjacent and in that order
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit"
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms


=== php rules ===

## PHP

- Always use curly braces for control structures, even if it has one line.

### Constructors
- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters.

### Type Declarations
- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Comments
- Prefer PHPDoc blocks over comments. Never use comments within the code itself unless there is something _very_ complex going on.

## PHPDoc Blocks
- Add useful array shape type definitions for arrays when appropriate.

## Enums
- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.


=== herd rules ===

## Laravel Herd

- The application is served by Laravel Herd and will be available at: https?://[kebab-case-project-dir].test. Use the `get-absolute-url` tool to generate URLs for the user to ensure valid URLs.
- You must not run any commands to make the site available via HTTP(s). It is _always_ available through Laravel Herd.


=== laravel/core rules ===

## Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Database
- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation
- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources
- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

### Controllers & Validation
- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

### Queues
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

### Authentication & Authorization
- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

### URL Generation
- When generating links to other pages, prefer named routes and the `route()` function.

### Configuration
- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

### Testing
- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] <name>` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

### Vite Error
- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.


=== laravel/v12 rules ===

## Laravel 12

- Use the `search-docs` tool to get version specific documentation.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

### Laravel 12 Structure
- No middleware files in `app/Http/Middleware/`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- **No app\Console\Kernel.php** - use `bootstrap/app.php` or `routes/console.php` for console configuration.
- **Commands auto-register** - files in `app/Console/Commands/` are automatically available and do not require manual registration.

### Database
- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 11 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models
- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.


=== pint/core rules ===

## Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.


=== tailwindcss/core rules ===

## Tailwind Core

- Use Tailwind CSS classes to style HTML, check and use existing tailwind conventions within the project before writing your own.
- Offer to extract repeated patterns into components that match the project's conventions (i.e. Blade, JSX, Vue, etc..)
- Think through class placement, order, priority, and defaults - remove redundant classes, add classes to parent or child carefully to limit repetition, group elements logically
- You can use the `search-docs` tool to get exact examples from the official documentation when needed.

### Spacing
- When listing items, use gap utilities for spacing, don't use margins.

    <code-snippet name="Valid Flex Gap Spacing Example" lang="html">
        <div class="flex gap-8">
            <div>Superior</div>
            <div>Michigan</div>
            <div>Erie</div>
        </div>
    </code-snippet>


### Dark Mode
- If existing pages and components support dark mode, new pages and components must support dark mode in a similar way, typically using `dark:`.


=== tailwindcss/v4 rules ===

## Tailwind 4

- Always use Tailwind CSS v4 - do not use the deprecated utilities.
- `corePlugins` is not supported in Tailwind v4.
- In Tailwind v4, you import Tailwind using a regular CSS `@import` statement, not using the `@tailwind` directives used in v3:

<code-snippet name="Tailwind v4 Import Tailwind Diff" lang="diff"
   - @tailwind base;
   - @tailwind components;
   - @tailwind utilities;
   + @import "tailwindcss";
</code-snippet>


### Replaced Utilities
- Tailwind v4 removed deprecated utilities. Do not use the deprecated option - use the replacement.
- Opacity values are still numeric.

| Deprecated |	Replacement |
|------------+--------------|
| bg-opacity-* | bg-black/* |
| text-opacity-* | text-black/* |
| border-opacity-* | border-black/* |
| divide-opacity-* | divide-black/* |
| ring-opacity-* | ring-black/* |
| placeholder-opacity-* | placeholder-black/* |
| flex-shrink-* | shrink-* |
| flex-grow-* | grow-* |
| overflow-ellipsis | text-ellipsis |
| decoration-slice | box-decoration-slice |
| decoration-clone | box-decoration-clone |


=== tests rules ===

## Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test` with a specific filename or filter.
</laravel-boost-guidelines>