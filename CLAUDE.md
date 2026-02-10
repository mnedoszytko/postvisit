# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**PostVisit.ai** — system AI utrzymujący kontekst konkretnej wizyty klinicznej, pomagający pacjentowi po wyjściu z gabinetu. Hackathon project: Built with Opus 4.6 (10-16 luty 2026).

Pełna dokumentacja projektu: `docs/seed.md`
Log decyzji: `docs/decisions.md`
Log błędów i poprawek: `docs/lessons.md`

## Continuous Improvement Process

When the user corrects a mistake, **immediately** log it in `docs/lessons.md` with: what went wrong, the correction, and the takeaway. Every 3-5 iterations, review `docs/lessons.md` and promote recurring patterns into this CLAUDE.md file.

## Stack

- **Backend**: Laravel, PHP 8.4
- **Frontend**: Vue 3, Vite
- **AI**: Claude Opus 4.6 (Anthropic SDK)
- **Hosting**: Laravel Forge + Hetzner
- **Development**: Laravel Herd (local)
- **Database / Cache**: TBD — do ustalenia przy scaffoldingu

## Essential Commands

### Development
- Never run `npm run dev` — dev server already runs in background via Herd
- Never run `php artisan serve` — Herd handles this automatically
- Local URL: `postvisit.test` (Herd convention based on directory name)

### Backend
- `php artisan test` — run all tests
- `php artisan test --filter=ClassName` — run single test class
- `php artisan migrate` — run migrations
- `./vendor/bin/pint` — fix PHP code style (Laravel Pint)

### Frontend
- `npm run build` — build for production
- `npx eslint resources/js --ext .vue,.js,.ts --fix` — fix JS/Vue code style

## Architecture

```
postvisit/
├── backend/            # Laravel API
├── frontend/           # Vue SPA
├── docs/               # dokumentacja robocza (seed.md, decisions.md, architecture.md)
├── prompts/            # system prompts dla Opus — wersjonowane jak kod
├── demo/               # dane demo, scenariusz, seed data medyczne
├── README.md
├── LICENSE
├── SECURITY.md
└── .env.example
```

### Prompts as Code
System prompts w `prompts/` są wersjonowane i review'owalne. Nie hardcode'uj promptów w kontrolerach — importuj z plików.

## Coding Guidelines

### Language Policy
- **Official project language: English.** All code, variable names, comments, commit messages, UI text, and repository documentation (README, SECURITY, etc.) MUST be in English.
- **Working docs in `docs/`** may be in Polish during development — they will be translated before final submission.
- **User prompts in chat** come in Polish and English — both are normal, always respond in the same language the user used.
- **Never translate or "correct" user's prompts** — the bilingual workflow is intentional and more effective.

### General Rules
- NEVER hardcode data to resolve a problem — only if explicitly instructed by user
- Never Co-Author commits with Claude Code as author

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

- Hackathon deadline: **16 lutego 2026, 15:00 EST** — priorytet to działający prototyp, nie perfekcyjna dokumentacja
