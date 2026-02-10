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
| Laravel 11 | MIT | Backend framework | ✅ OK |
| Vue 3 | MIT | Frontend framework | ✅ OK |
| Vite | MIT | Build tool | ✅ OK |
| Anthropic PHP SDK | MIT | API client | ✅ OK |
| php-fhir (dcarbone) | Apache 2.0 | FHIR classes | ✅ OK |

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

- [ ] Cały nasz kod pod MIT license
- [ ] `LICENSE` file w root repo
- [ ] Bundlowane guidelines mają `ATTRIBUTION.md` z atrybucjami
- [ ] Żadne proprietary data w repo
- [ ] Demo video pokazuje tylko open source komponenty
- [ ] README zawiera disclaimer: "Demo only, not for clinical use"
- [ ] `.env.example` bez prawdziwych kluczy API
