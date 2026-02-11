# Licenses & Hackathon Compliance Tracker

> Żywy dokument — aktualizowany przy każdej zmianie stacku.
> Ostatnia aktualizacja: 2026-02-10

## Zasady hackathonu (przypomnienie)

1. **Open Source** — wszystko pokazane w demo musi być open source (backend, frontend, modele, inne komponenty)
2. **New Work Only** — projekt od zera w trakcie hackathonu
3. **Team Size** — do 2 osób
4. **Banned** — dyskwalifikacja za: naruszenie prawa/etyki/policies platform, użycie kodu/danych/assetów bez praw

## Nasz kod

| Komponent | Licencja | Status |
|-----------|----------|--------|
| Backend (Laravel app) | MIT (nasze) | ✅ OK |
| Frontend (Vue app) | MIT (nasze) | ✅ OK |
| System prompts (`prompts/`) | MIT (nasze) | ✅ OK |
| Demo data (`demo/`) | MIT (nasze, napisane przez lekarza) | ✅ OK |

## Frameworki i biblioteki

| Dependencja | Licencja | Typ | Status |
|-------------|----------|-----|--------|
| Laravel 12 | MIT | Backend framework | ✅ OK |
| Vue 3 | MIT | Frontend framework | ✅ OK |
| Vue Router 4 | MIT | Client-side routing | ✅ OK |
| Pinia | MIT | State management | ✅ OK |
| Vite | MIT | Build tool | ✅ OK |
| Tailwind CSS v4 | MIT | CSS framework | ✅ OK |
| Axios | MIT | HTTP client | ✅ OK |
| Laravel Sanctum | MIT | SPA authentication | ✅ OK |
| Anthropic PHP SDK (`anthropic-ai/laravel`) | MIT | AI API client | ✅ OK |
| Bun | MIT | Package manager / runtime | ✅ OK |

## Model AI

| Model | Licencja | Uwagi | Status |
|-------|----------|-------|--------|
| Claude Opus 4.6 | Proprietary (Anthropic API) | Hackathon organizowany przez Anthropic — użycie Opus jest oczekiwane. Nasz kod integracyjny jest open source. | ✅ OK |

## STT (Speech-to-Text)

| Serwis | Licencja | Rola | Status |
|--------|----------|------|--------|
| Whisper (OpenAI) | MIT | Primary — na demo | ✅ OK |
| whisper.cpp | MIT | Alternatywa (szybsza) | ✅ OK |
| WhisperX | BSD-4 | Alternatywa (z diaryzacją) | ✅ OK |
| Google Cloud STT | Proprietary API | Cloud option — nasz adapter open source | ⚠️ Serwis zewnętrzny, nie komponent demo |
| iOS Speech Framework | Apple proprietary | On-device option — platformowe API | ⚠️ Serwis platformowy, nie komponent demo |
| Deepgram Nova-3 | Proprietary API | Fallback (medical) — nie na demo | ⚠️ Tylko fallback |

**Na demo video:** Whisper (MIT, open source, bezsprzeczne).

## Dane kliniczne — bundlowane w repo

| Zasób | Licencja | Czy można w repo? | Status |
|-------|----------|-------------------|--------|
| ESC guidelines (z journala) | CC-BY | ✅ z atrybucją | ✅ OK |
| AHA guidelines (z journala) | CC-BY | ✅ z atrybucją | ✅ OK |
| WHO guidelines | CC-BY 3.0 IGO | ✅ z atrybucją | ✅ OK |
| RxNorm data | Public domain (NLM) | ✅ | ✅ OK |
| OpenFDA data | Public domain | ✅ | ✅ OK |
| DailyMed data | Public domain | ✅ | ✅ OK |
| ICD-11 data | CC-BY-ND 3.0 IGO | ✅ z atrybucją, bez modyfikacji | ✅ OK |

### NIE wrzucać do repo

| Zasób | Licencja | Dlaczego nie | Status |
|-------|----------|--------------|--------|
| NICE guidelines | CC-BY-NC | Non-commercial — ryzykowne | ❌ NIE |
| UpToDate content | Proprietary | Subscription, copyright | ❌ NIE |
| DynaMed content | Proprietary | Subscription, copyright | ❌ NIE |
| BMJ Best Practice | Proprietary | Subscription, copyright | ❌ NIE |
| Cochrane reviews | Proprietary (Wiley) | Subscription | ❌ NIE |

## Zewnętrzne API (serwisy, nie komponenty)

Serwisy wywoływane przez nasz open source kod. Nie są "komponentami demo" — to infrastruktura.

| API | Licencja danych | Auth | Koszt | Status |
|-----|----------------|------|-------|--------|
| PubMed E-utilities | Public (NLM) | Free API key | Free | ✅ OK |
| Europe PMC | Varies (per article) | None | Free | ✅ OK |
| ClinicalTrials.gov v2.0 | Public | None | Free | ✅ OK |
| OpenAlex | CC0 (metadata) | Free API key | Free | ✅ OK |
| OpenFDA | Public domain | None | Free | ✅ OK |
| RxNorm (RxNav) | Public domain (NLM) | None | Free | ✅ OK |
| ICD-11 API (WHO) | CC-BY-ND 3.0 IGO | None | Free | ✅ OK |
| NIH Clinical Tables | Public | None | Free | ✅ OK |
| Semantic Scholar | Free tier | Free API key | Free | ✅ OK |
| DrugBank | Proprietary (free tier) | API key | Free tier | ⚠️ sprawdzić ToS |
| OpenEvidence | Proprietary (free tier) | NPI verification | Free | ⚠️ wymaga NPI |

## Infrastruktura (nie dotyczy reguł)

| Serwis | Cel | Uwagi |
|--------|-----|-------|
| Laravel Forge | Deploy | Narzędzie, nie komponent |
| Hetzner | Hosting | Serwer, nie komponent |
| Let's Encrypt | TLS | Free, open source CA |
| GitHub | Repo | Wymagane przez hackathon |

## Licencja projektu

**MIT** — najprostsza, najszerzej akceptowana. Kompatybilna ze wszystkimi dependencjami.

Plik `LICENSE` w root repo.

## Checklist przed submission

> Full rules & judging criteria: `docs/hackathon-rules.md`
> Deadline: **Mon Feb 16, 3:00 PM EST**

### Submission (wymagane na platformie CV)
- [ ] Demo video uploaded (YouTube/Loom) — **max 3 minuty**
- [ ] GitHub repo link — **public**
- [ ] Written summary — **100–200 words**, po angielsku

### Repo & Licencje
- [ ] `LICENSE` file w root (MIT)
- [ ] Cały nasz kod pod MIT license
- [ ] `ATTRIBUTION.md` z atrybucjami dla bundlowanych guidelines (ESC, AHA, WHO, RxNorm)
- [ ] Żadne proprietary data w repo (NICE, UpToDate, Cochrane — NIE)
- [ ] `docs/licenses.md` aktualny — wszystkie deps, API, data sources

### Secrets & Security
- [ ] `.env.example` z wszystkimi wymaganymi kluczami (bez wartości!)
- [ ] `.env` w `.gitignore` (nigdy w repo)
- [ ] Żadne API keys / tokeny / hasła w kodzie ani w git history
- [ ] Anthropic API key tylko w `.env`

### Kod & Testy
- [ ] `herd php artisan test` — wszystkie testy przechodzą
- [ ] `bun run build` — frontend kompiluje bez błędów
- [ ] `vendor/bin/pint --dirty` — code style OK
- [ ] Brak `dd()`, `dump()`, `console.log()` debug statements

### Dokumentacja (hackathon judging criterion — Depth & Execution 20%)
- [ ] `README.md` — project overview, setup, architecture, demo guide (English!)
- [ ] `docs/api.md` — endpointy z examples
- [ ] `docs/architecture.md` — system architecture, data flow, AI pipeline
- [ ] `docs/ai-prompts.md` — każdy prompt udokumentowany
- [ ] `CHANGELOG.md` — feature changelog
- [ ] Wszystkie docs przetłumaczone na **English**

### Demo Video (30% of score — najważniejsza kategoria!)
- [ ] Wszystkie must-have features działają na żywo
- [ ] Screen recordings w 16:9
- [ ] Napisy burned-in
- [ ] Storytelling: problem → solution → wow moment
- [ ] Pokazuje Opus 4.6 capabilities (1M context, extended thinking)
- [ ] Max 3 minuty — ani sekundy więcej

### Opus 4.6 Use (25% of score)
- [ ] 1M context window wyraźnie wykorzystany (visit + guidelines + patient history w jednym prompcie)
- [ ] Extended thinking widoczne w demo
- [ ] Prompt caching na guidelines (90% savings)
- [ ] Coś zaskakującego — "capabilities that surprised even us"

### Impact (25% of score)
- [ ] README jasno opisuje problem i kto korzysta
- [ ] Disclaimer: "Prototype — not for clinical use"
- [ ] Mapowanie na problem statements (all 3 tracks)

### Self-evaluation (przed submitem)
- [ ] Puścić Opusa jako "judge" na nasze repo — ocena wg 4 kryteriów
- [ ] Poprawić najsłabsze punkty
- [ ] Sprawdzić czy README/video odpowiadają na pytania sędziów
