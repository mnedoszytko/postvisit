# Hackathon Rules & Judging — Built with Opus 4.6

> Source: Official event page (Cerebral Valley / Anthropic), pasted 2026-02-11

## Key Dates

| Event | Date & Time (EST) |
|-------|-------------------|
| Hacking begins | Tue Feb 10, 12:30 PM |
| **Submission deadline** | **Mon Feb 16, 3:00 PM** |
| Stage 1 — Async judging | Feb 16–17 |
| Top 6 announced | Wed Feb 18, 12:00 PM |
| Closing ceremony / Top 3 revealed | Wed Feb 18, 1:30 PM |
| Winners showcase (SF) | Sat Feb 21 |

## Rules

1. **Open Source** — Everything shown in the demo must be fully open source (backend, frontend, models, other components) under an approved open source license.
2. **New Work Only** — All projects must be started from scratch during the hackathon. No pre-existing work.
3. **Team Size** — Up to 2 members. Solo OK.
4. **Banned** — Disqualification if project: violates legal/ethical/platform policies, uses code/data/assets without rights.

## Submission Requirements

| Item | Requirement |
|------|-------------|
| Demo video | **3-minute maximum** (YouTube, Loom, or similar) |
| Code | GitHub repository or code link (public) |
| Written summary | **100–200 words** |
| Platform | Submit via Cerebral Valley platform |

## Judging — Stage 1 (Async)

Six judges from Anthropic evaluate independently using standardized criteria:

### 1. Demo — 30%

> Is this a working, impressive demo? Does it hold up live? Is it genuinely cool to watch?

### 2. Impact — 25%

> What's the real-world potential here? Who benefits, and how much does it matter? Could this actually become something people use? Does it fit into one of the problem statements?

### 3. Opus 4.6 Use — 25%

> How creatively did this team use Opus 4.6? Did they go beyond a basic integration? Did they surface capabilities that surprised even us?

### 4. Depth & Execution — 20%

> Did the team push past their first idea? Is the engineering sound and thoughtfully refined? Does this feel like something that was wrestled with — real craft, not just a quick hack?

## Judging — Stage 2 (Live)

Top 6 teams. Pre-recorded 3-min demos played, then judges deliberate.

## Prizes

| Place | Prize |
|-------|-------|
| 1st | $50,000 API credits |
| 2nd | $30,000 API credits |
| 3rd | $10,000 API credits |
| **Most Creative Opus 4.6 Exploration** | $5,000 API credits |
| **The "Keep Thinking" Prize** | $5,000 API credits |

### Special Prize Descriptions

**Most Creative Opus 4.6 Exploration:**
> This prize is for the team that found the most interesting edge of this new model — the unexpected capability or the use case nobody thought to try. We want the project that taught us something new about what Opus 4.6 can do.

**The "Keep Thinking" Prize:**
> For the project that didn't stop at the first idea. We're looking for the team that pushed past the obvious, iterated relentlessly, and showed the kind of depth that turns a good hack into something genuinely surprising.

## Problem Statements (3 tracks)

### Track 1: Build a Tool That Should Exist
Create the AI-native app or workflow you wish someone had already built. Eliminate busywork. Make hard things effortless.

### Track 2: Break the Barriers
Expert knowledge, essential tools, AI's benefits — take something powerful that's locked behind expertise, cost, language, or infrastructure and put it in everyone's hands.

### Track 3: Amplify Human Judgment
Build AI that makes researchers, professionals, and decision-makers dramatically more capable — without taking them out of the loop.

**PostVisit.ai maps to ALL THREE tracks** (see `docs/seed.md` § 0.1).

## Judges

Boris Cherny, Cat Wu, Thariq Shihipar, Lydia Hallie, Ado Kukic, Jason Bigman — all from Anthropic / Claude Code team.

## PostVisit.ai — Strategy Notes

### Gdzie jesteśmy mocni
- **Impact (25%)** — Healthcare, real problem, real doctor building it. Trudno o lepszy "who benefits and how much does it matter".
- **Demo (30%)** — Scenariusz z prawdziwym lekarzem, prawdziwe nagranie wizyty, Golden Gate Bridge. Storytelling > slides.

### Gdzie musimy docisnąć
- **Opus 4.6 Use (25%)** — Musimy jasno pokazać: 1M context window (cały kontekst wizyty + guidelines + patient history w jednym prompcie), extended thinking, prompt caching, 128K output. Nie wystarczy "używamy Opusa do chatu".
- **Depth & Execution (20%)** — Iteracja, craft, nie "quick hack". Git history, docs, architecture decisions, test coverage.

### Special prizes — nasze szanse
- **"Most Creative Opus 4.6 Exploration"** — "Reverse scribe" (patient-initiated transcription) + 1M context window z guidelines + patient health record to unikalne combo. Warto podkreślić.
- **"Keep Thinking"** — Iteracja jest naszą siłą: decisions.md z 26 decyzjami, lessons learned, architecture evolution. Warto pokazać w README/video.
