# Decyzje projektowe — log dyskusji

Ten dokument zapisuje wszystkie decyzje podjęte w trakcie projektowania PostVisit.ai.

## Data: 2026-02-10

### Decyzja 1: Struktura repozytorium — Integrated Laravel + Vue Monorepo
**Status:** Przyjęte (updated 2026-02-10)

Jeden repo, zintegrowane Laravel + Vue (Vue w `resources/js/`, nie osobny katalog). See Decyzja 19.

```
postvisit/
├── app/                # Laravel application (controllers, models, services)
├── resources/js/       # Vue 3 SPA (integrated)
├── docs/               # dokumentacja robocza
├── prompts/            # system prompts dla Opus (wersjonowane jak kod)
├── demo/               # dane demo, scenariusze, seed data
├── database/           # migrations, seeders, factories
├── tests/              # PHPUnit tests (67 tests)
├── CLAUDE.md
├── README.md
├── LICENSE
├── SECURITY.md
└── .env.example
```

### Decyzja 2: Dane demo — pisane przez lekarza
**Status:** Przyjęte

Użytkownik (lekarz) napisze realistyczny wypis lekarski, zalecenia i dane pacjenta. Scenariusz z seed.md (pobudzenia komorowe, propranolol) jest bazą, ale zostanie rozbudowany do pełnego, wiarygodnego dokumentu medycznego.

### Decyzja 3: Ambient scribing / transkrypcja — MUSI być w demo
**Status:** Przyjęte

Transkrypcja rozmowy lekarz-pacjent jest krytycznym elementem demo. Bez tego demo nie ma sensu. System musi pokazać jak transkrypt jest źródłem kontekstu dla AI.

### Decyzja 4: Doctor-in-the-loop — oba widoki
**Status:** Przyjęte

Demo będzie zawierać:
- **Widok pacjenta** — główny ekran z wizytą, Q&A, wyjaśnienia
- **Widok lekarza** — dashboard z feedbackiem, kontekstem

Film demo pokaże obie strony.

### Decyzja 5: Hosting — Forge + Hetzner
**Status:** Przyjęte

- Deploy via Laravel Forge
- Serwer na Hetznerze
- Claude Code ma dostęp do Hetznera przez API
- Development lokalny na MacBook Air z Herd

### Decyzja 6: Kontekst AI — wizyta + wytyczne kliniczne
**Status:** Przyjęte

Kontekst dla Opus 4.6 nie jest ograniczony do danych wizyty. Zawiera również:
- Dane z konkretnej wizyty (wypis, leki, badania, transkrypcja)
- **Wytyczne kliniczne** (np. ESC — European Society of Cardiology, AHA — American Heart Association)
- Pozwala Opus na odpowiedzi oparte o evidence-based medicine, w kontekście tego konkretnego pacjenta

To silny punkt dla hackathonu — pokazuje kreatywne użycie 1M token context window Opus 4.6.

### Decyzja 7: Pliki repo wymagane dla hackathonu
**Status:** Przyjęte

Na podstawie analizy wymagań hackathonu i najlepszych praktyk:

| Plik | Cel | Priorytet |
|------|-----|-----------|
| `README.md` | Pierwsze co widzą sędziowie — musi być doskonały | Krytyczny |
| `LICENSE` | Open source wymagane — MIT | Krytyczny |
| `.env.example` | Pokazuje profesjonalne podejście do secrets | Krytyczny |
| `CLAUDE.md` | Antropic-specific — pokazuje głęboką integrację z Claude Code | Krytyczny |
| `SECURITY.md` | Healthcare AI — bezpieczeństwo to must-have | Wysoki |
| `docs/architecture.md` | Pokazuje przemyślany design | Wysoki |
| Disclaimer w README | "Demo only, no real patient data, not for clinical use" | Krytyczny |

### Decyzja 8: Scenariusze demo video
**Status:** Oczekuje

Użytkownik wklei 2 scenariusze filmiku. Czekamy na dane.

### Decyzja 9: Hackathon — kluczowe fakty
**Status:** Informacja

- **Hackathon:** Built with Opus 4.6 (Anthropic + Cerebral Valley)
- **Daty:** 10-16 lutego 2026
- **Deadline:** Poniedziałek 16 lutego, 15:00 EST
- **Nagrody:** $100K w API credits ($50K/1st, $30K/2nd, $10K/3rd + 2x $5K special)
- **Special prizes:** "Most Creative Opus 4.6 Exploration" i "The Keep Thinking Prize"
- **Sędziowie:** 6 osób z Anthropic (Boris Cherny, Cat Wu, Thariq Shihpar, Lydia Hallie, Ado Kukic, Jason Bigman)
- **Winners showcase:** 21 lutego w SF
- **Submission:** GitHub repo + demo video (1-5 min) + opis projektu (limit słów: sprawdzić na portalu — prawdopodobnie 200)

### Decyzja 10: Model policy — Sonnet OK do testów
**Status:** Przyjęte

- **Produkcja / demo:** Opus 4.6
- **Testy / development:** Sonnet jest OK (optymalizacja kosztów)
- **Subagenty (Task tool):** zawsze Opus

### Decyzja 11: Kontekst AI — do osobnej dyskusji
**Status:** Odłożone

Źródła kontekstu AI (dane wizyty, wytyczne kliniczne, guardrails) będą szczegółowo omówione w dedykowanej dyskusji. Usunięte z CLAUDE.md do czasu ustalenia.

### Decyzja 12: PHP 8.4
**Status:** Przyjęte

PHP 8.4 — bez dyskusji. Baza danych i cache do ustalenia przy scaffoldingu.

### Decyzja 13: Baza danych — PostgreSQL
**Status:** Przyjęte (zmiana z "Odłożone")

PostgreSQL — decyzja podjęta na podstawie analizy data-model.md:
- **jsonb** — natywne indeksowanie na `specialty_data`, `extracted_entities`, `diarized_transcript`
- **tsvector** — full-text search na transkryptach i notatkach klinicznych
- **UUID** — natywny typ (nie varchar)
- **Partitioning** — audit_logs partitioned by month
- Standard w healthcare (HIPAA/SOC2)

Cache i CSS framework — do ustalenia przy scaffoldingu.

### Decyzja 14: Agent Teams — włączone
**Status:** Przyjęte

Włączony eksperymentalny feature `CLAUDE_CODE_EXPERIMENTAL_AGENT_TEAMS` w `~/.claude/settings.json`. Pozwala na spawning zespołów agentów, które pracują równolegle i mogą dyskutować między sobą (np. backend + frontend + devil's advocate). Nowa funkcja Opus 4.6 — aktywna po restarcie terminala.

### Decyzja 15: Demo video — orientacja i napisy
**Status:** Do ustalenia

**Orientacja:**
Film musi pokazać dwie rzeczy: UI aplikacji (iOS mobile) i architekturę/flow systemu.

Opcje:
- **Horyzontalny (16:9)** — standard dla software demo. Łatwiej pokazać split screen (phone mockup + schemat architektury obok). Sędziowie oglądają na laptopie. Większość hackathonowych filmów jest landscape.
- **Wertykalny (9:16)** — naturalny dla mobile app. Ale sędziowie raczej nie oglądają na telefonie, i trudno zmieścić tekst/schematy obok.
- **Horyzontalny z phone mockup w centrum** — kompromis: landscape frame, w środku telefon z apką, po bokach context/architektura.

**Napisy / captions:**
- Muszą być — sędziowie mogą oglądać bez dźwięku
- Burned-in (hardcoded w wideo) vs. osobny plik (.srt)

Narzędzia do rozważenia:
- **CapCut** — free, auto-captions, dobre style
- **Descript** — transkrypcja + edycja tekstu = edycja wideo
- **DaVinci Resolve** — free, professional, ale krzywa uczenia
- **Whisper + ffmpeg** — generuj .srt z Whisper, burn-in przez ffmpeg (full open source pipeline)

Styl napisów: krótkie, keyword-heavy, wyjaśniające co się dzieje na ekranie (nie pełny voiceover transcript).

### Decyzja 16: Data model — FHIR R4, diagnostic_reports usunięte
**Status:** Przyjęte

Data model w `docs/data-model.md` — 17 tabel, FHIR R4 aligned. `diagnostic_reports` usunięte (duplikacja z observations + documents + visit_notes). `roles` uproszczone do enum na demo. `consents` tabela wyłączona z demo.

### Decyzja 17: Medications — RxNorm API + local cache
**Status:** Przyjęte

Tabela `medications` działa jako local cache. Propranolol seeded (demo niezawodność). Reszta leków fetched z RxNorm API on-demand (`rxnav.nlm.nih.gov/REST/`). Sędziowie mogą wyszukać dowolny lek.

### Decyzja 18: API — REST na demo, interoperability-first
**Status:** Przyjęte

Demo: Laravel REST API + Sanctum. Ale architektura od dnia zero zakłada:
- Interoperability (FHIR R4 export endpoints — roadmap)
- Agent-friendly API (GraphQL layer — roadmap)
- Ecosystem integration (webhooks, CDS Hooks — roadmap)

PostVisit.ai to NIE standalone wyspa — to skalowalny produkt w healthcare ekosystemie.

### Decyzja 19: Integrated Laravel + Vue (zmiana z osobnych katalogów)
**Status:** Przyjęte (2026-02-10)

Zmiana z `backend/` + `frontend/` na zintegrowaną architekturę:
- Vue 3 w `resources/js/` (standard Laravel)
- Zero CORS issues (same-origin)
- Simpler auth (Sanctum cookie-based)
- Faster development for hackathon
- API (`/api/v1/`) remains standalone and fully accessible

### Decyzja 20: Bun zamiast npm
**Status:** Przyjęte (2026-02-10)

Bun jako package manager zamiast npm. Szybszy install, szybszy build. Bun 1.3.9.

### Decyzja 21: Cache i Queue — Database driver
**Status:** Przyjęte (2026-02-10)

Database driver (PostgreSQL) dla cache i queue. Prostsze niż Redis, wystarczające na hackathon. Zero dodatkowej infrastruktury.

### Decyzja 22: Tailwind CSS v4 (not v3)
**Status:** Przyjęte (2026-02-10)

Laravel 12 ships with Tailwind CSS v4. Using native integration, no separate tailwind.config.js needed (CSS-first config via `@theme`).

### Decyzja 23: PrimeVue 4 as UI component library
**Status:** Przyjęte (2026-02-10)

PrimeVue 4 + Aura theme for production-quality UI. Replaces custom Tailwind-only components. See POST-1.

### Decyzja 24: Linear for project management
**Status:** Przyjęte (2026-02-10)

Linear (team POST in medduties workspace) for issue tracking. GraphQL API access via `$LINEAR_API_KEY`. All issues tagged `agent-ready` can be worked on autonomously.

### Decyzja 25: Voice chat via OpenAI Whisper + TTS
**Status:** Przyjęte (2026-02-10)

MediaRecorder in browser → POST to Laravel → proxy to OpenAI Whisper API for STT. Optional TTS via OpenAI TTS API. See POST-16.

### Decyzja 26: Testing strategy — PHPUnit + SQLite in-memory
**Status:** Przyjęte (2026-02-10)

67 feature tests, 175 assertions, <1s runtime. SQLite in-memory for speed. PostgreSQL-specific features (ilike) handled with conditional logic for test compatibility.

## Data: 2026-02-11

### Decyzja 27: Medical term highlighting — jsonb offsets, not inline HTML or real-time extraction
**Status:** Przyjęte (2026-02-11)

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
