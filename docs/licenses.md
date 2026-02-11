# Licenses & Hackathon Compliance Tracker

> Living document -- updated with every stack change.
> Last updated: 2026-02-11

## Hackathon Rules (Reminder)

1. **Open Source** -- everything shown in the demo must be open source (backend, frontend, models, other components)
2. **New Work Only** -- project built from scratch during the hackathon
3. **Team Size** -- up to 2 people
4. **Banned** -- disqualification for: violating law/ethics/platform policies, using code/data/assets without rights

## Our Code

| Component | License | Status |
|-----------|---------|--------|
| Backend (Laravel app) | MIT (ours) | OK |
| Frontend (Vue app) | MIT (ours) | OK |
| System prompts (`prompts/`) | MIT (ours) | OK |
| Demo data (`demo/`) | MIT (ours, written by a physician) | OK |

## Frameworks & Libraries

| Dependency | License | Type | Status |
|------------|---------|------|--------|
| Laravel 12 | MIT | Backend framework | OK |
| Vue 3 | MIT | Frontend framework | OK |
| Vue Router 4 | MIT | Client-side routing | OK |
| Pinia | MIT | State management | OK |
| Vite | MIT | Build tool | OK |
| Tailwind CSS v4 | MIT | CSS framework | OK |
| Axios | MIT | HTTP client | OK |
| qrcode.vue | MIT | QR code generation (Vue 3) | OK |
| Laravel Sanctum | MIT | SPA authentication | OK |
| Anthropic PHP SDK (`anthropic-ai/laravel`) | MIT | AI API client | OK |
| Bun | MIT | Package manager / runtime | OK |

## AI Model

| Model | License | Notes | Status |
|-------|---------|-------|--------|
| Claude Opus 4.6 | Proprietary (Anthropic API) | Hackathon organized by Anthropic -- Opus usage is expected. Our integration code is open source. | OK |

## STT (Speech-to-Text)

| Service | License | Role | Status |
|---------|---------|------|--------|
| Whisper (OpenAI) | MIT | Primary -- used in demo | OK |
| whisper.cpp | MIT | Alternative (faster) | OK |
| WhisperX | BSD-4 | Alternative (with diarization) | OK |
| Google Cloud STT | Proprietary API | Cloud option -- our adapter is open source | External service, not a demo component |
| iOS Speech Framework | Apple proprietary | On-device option -- platform API | Platform service, not a demo component |
| Deepgram Nova-3 | Proprietary API | Fallback (medical) -- not in demo | Fallback only |

**Demo video uses:** Whisper (MIT, open source, unambiguous).

## Clinical Data -- Bundled in Repo

| Resource | License | Bundled in Repo? | Status |
|----------|---------|------------------|--------|
| WHO guidelines | CC-BY 3.0 IGO | Yes, with attribution | OK |
| RxNorm data | Public domain (NLM) | Yes | OK |
| OpenFDA data | Public domain | Yes | OK |
| DailyMed data | Public domain | Yes | OK |
| ICD-11 data | CC-BY-ND 3.0 IGO | Yes, with attribution, no modifications | OK |

### Do NOT Bundle in Repo

| Resource | License | Reason | Status |
|----------|---------|--------|--------|
| UpToDate content | Proprietary | Subscription, copyright | NO |
| DynaMed content | Proprietary | Subscription, copyright | NO |
| BMJ Best Practice | Proprietary | Subscription, copyright | NO |
| Cochrane reviews | Proprietary (Wiley) | Subscription | NO |

## External APIs (Services, Not Components)

Services called by our open source code. They are not "demo components" -- they are infrastructure.

| API | Data License | Auth | Cost | Status |
|-----|-------------|------|------|--------|
| PubMed E-utilities | Public (NLM) | Free API key | Free | OK |
| Europe PMC | Varies (per article) | None | Free | OK |
| ClinicalTrials.gov v2.0 | Public | None | Free | OK |
| OpenAlex | CC0 (metadata) | Free API key | Free | OK |
| OpenFDA | Public domain | None | Free | OK |
| RxNorm (RxNav) | Public domain (NLM) | None | Free | OK |
| ICD-11 API (WHO) | CC-BY-ND 3.0 IGO | None | Free | OK |
| NIH Clinical Tables | Public | None | Free | OK |
| Semantic Scholar | Free tier | Free API key | Free | OK |
| DrugBank | Proprietary (free tier) | API key | Free tier | Check ToS |
| OpenEvidence | Proprietary (free tier) | NPI verification | Free | Requires NPI |

## Infrastructure (Not Subject to Rules)

| Service | Purpose | Notes |
|---------|---------|-------|
| Laravel Forge | Deploy | Tool, not a component |
| Hetzner | Hosting | Server, not a component |
| Let's Encrypt | TLS | Free, open source CA |
| GitHub | Repo | Required by hackathon |

## Project License

**MIT** -- simplest, most widely accepted. Compatible with all dependencies.

`LICENSE` file in repo root.

## Pre-Submission Checklist

> Full rules & judging criteria: `docs/hackathon-rules.md`
> Deadline: **Mon Feb 16, 3:00 PM EST**

### Submission (Required on CV Platform)
- [ ] Demo video uploaded (YouTube/Loom) -- **max 3 minutes**
- [ ] GitHub repo link -- **public**
- [ ] Written summary -- **100-200 words**, in English

### Repo & Licenses
- [ ] `LICENSE` file in root (MIT)
- [ ] All our code under MIT license
- [ ] `ATTRIBUTION.md` with attributions for bundled guidelines (WikiDoc, DailyMed, etc.)
- [ ] No proprietary data in repo (NICE, UpToDate, Cochrane -- NO)
- [ ] `docs/licenses.md` up to date -- all deps, APIs, data sources

### Secrets & Security
- [ ] `.env.example` with all required keys (no values!)
- [ ] `.env` in `.gitignore` (never in repo)
- [ ] No API keys / tokens / passwords in code or git history
- [ ] Anthropic API key only in `.env`

### Code & Tests
- [ ] `herd php artisan test` -- all tests pass
- [ ] `bun run build` -- frontend compiles without errors
- [ ] `vendor/bin/pint --dirty` -- code style OK
- [ ] No `dd()`, `dump()`, `console.log()` debug statements

### Documentation (Hackathon Judging Criterion -- Depth & Execution 20%)
- [ ] `README.md` -- project overview, setup, architecture, demo guide (English!)
- [ ] `docs/api.md` -- endpoints with examples
- [ ] `docs/architecture.md` -- system architecture, data flow, AI pipeline
- [ ] `docs/ai-prompts.md` -- every prompt documented
- [ ] `CHANGELOG.md` -- feature changelog
- [ ] All docs translated to **English**

### Demo Video (30% of Score -- Most Important Category!)
- [ ] All must-have features work live
- [ ] Screen recordings in 16:9
- [ ] Burned-in subtitles
- [ ] Storytelling: problem -> solution -> wow moment
- [ ] Shows Opus 4.6 capabilities (1M context, extended thinking)
- [ ] Max 3 minutes -- not a second more

### Opus 4.6 Use (25% of Score)
- [ ] 1M context window clearly utilized (visit + guidelines + patient history in one prompt)
- [ ] Extended thinking visible in demo
- [ ] Prompt caching on guidelines (90% savings)
- [ ] Something surprising -- "capabilities that surprised even us"

### Impact (25% of Score)
- [ ] README clearly describes the problem and who benefits
- [ ] Disclaimer: "Prototype -- not for clinical use"
- [ ] Mapping to problem statements (all 3 tracks)

### Self-Evaluation (Before Submission)
- [ ] Run Opus as "judge" on our repo -- score per 4 criteria
- [ ] Fix weakest points
- [ ] Verify README/video answer judges' questions

## Clinical Guidelines Knowledge Base

### Licensing Analysis

We conducted a thorough analysis of clinical practice guideline licensing for AI/LLM use. Key findings:

- **ESC Guidelines**: Copyrighted with explicit AI opt-out under EU Directive 2019/790 Article 4(3). Cannot be used for training, RAG, or bundling without a formal license agreement.
- **AHA/ACC Guidelines**: Full copyright held by ACC Foundation and AHA. No structured/machine-readable data available. No redistribution permitted.
- **NICE Guidelines**: UK Open Content Licence for UK use, but AI use explicitly requires written permission and a signed license agreement.

This forced us to design a 3-layer compliance-first architecture:

| Source | License | Usage | Bundled in Repo? |
|--------|---------|-------|-----------------|
| WikiDoc | CC-BY-SA 3.0 | Cardiology reference articles | Yes -- `demo/guidelines/wikidoc/` |
| DailyMed (NLM) | Public Domain (US Gov) | Drug label summaries | Yes -- `demo/guidelines/dailymed/` |
| PMC Open Access (NLM) | CC-BY / CC-BY-NC per article | Runtime guideline RAG via BioC API | No -- fetched at runtime, cached 24h |
| Own clinical summaries | Original work | Derivative summaries in `prompts/guidelines/` | Yes |
| ESC Guidelines | Copyrighted + EU AI opt-out | **NOT USED** | No |
| AHA/ACC Guidelines | Copyrighted | **NOT USED** directly -- PMC OA versions fetched at runtime only | No |
| NICE Guidelines | Requires written AI license | **NOT USED** | No |
