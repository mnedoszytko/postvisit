# Lessons Learned — Bug Fixes and Corrections Log

This file records mistakes made by Claude and corrections from the user.
Every 3-5 iterations, we review and promote the most important takeaways to CLAUDE.md.

**Last promotion:** 2026-02-11 — 16 lessons promoted to CLAUDE.md (Field Audit Rule, Ephemeral Data Protection, AI Output Validation, Demo Seeder Completeness, Axios Interceptor Safety).

## 2026-02-10

### Lesson 1: Project Name MedDuties
- **Bug:** Claude searched for project "MedNutis" — wrong name
- **Fix:** The project is called **MedDuties** (directory `../dyzury`)
- **Takeaway:** When the user gives a project name, ask for clarification if not found — don't guess

### Lesson 2: Don't Carry Assumptions from Other Projects
- **Bug:** CLAUDE.md contained "MySQL, Redis, PHP 8.2+" — copied from PreVisit without asking
- **Fix:** PHP 8.4 (no discussion needed), database and cache to be decided
- **Takeaway:** Each project has its own assumptions. Don't copy the stack from sibling projects — ask or mark as TBD

### Lesson 3: Report Progress Context
- **Bug:** Did not provide section numbers in context of the whole (e.g. "section 4" without "of 11")
- **Fix:** Always say "section X of Y" so the user knows what stage they're at
- **Takeaway:** When working iteratively section by section, always provide progress context

### Lesson 4: Smaller Chunks for Review
- **Bug:** Section 11 (Out of Scope) presented as a large block of text — user couldn't process it
- **Fix:** Present in smaller portions, give time to read each one
- **Takeaway:** When presenting content for review — max 1 table or 5-8 bullet points at a time. Better 3 short messages than 1 wall of text

### Lesson 9: Sanctum TransientToken has no delete() method
- **Bug:** `AuthController::logout()` called `$request->user()->currentAccessToken()->delete()` which crashes with cookie-based SPA auth because Sanctum returns a `TransientToken` (not a `PersonalAccessToken`)
- **Fix:** Check `method_exists($token, 'delete')` before calling. Also invalidate session + regenerate CSRF for cookie auth.
- **Takeaway:** Sanctum SPA auth uses sessions, not tokens. Always handle both auth modes (token + session) in logout.

## 2026-02-12

### Lesson 18: Never run long AI generation tasks without explicit user approval
- **Bug:** Agent autonomously ran `app:generate-scenario-notes` for 10 scenarios (~20 min of Opus API time) without asking first.
- **Root cause:** Agent treated the task assignment as blanket permission to run expensive AI calls.
- **Fix:** Any batch AI generation (SOAP notes, term extraction, transcript processing) requires **explicit user confirmation** before starting. These calls are expensive (tokens) and slow (minutes per scenario). During a hackathon, time is the scarcest resource.
- **Rule:** Before running any AI generation command, always ask: "This will call Claude Opus for N scenarios, ~X minutes. Proceed?"

### Lesson 17: Never regenerate AI content that already exists — token cost is massive
- **Bug:** Agent ran AI generation (Claude Opus) for scenarios that already had pre-generated SOAP notes and medical terms, burning tokens unnecessarily.
- **Root cause:** Not checking for existing files before calling AI, or re-running generation with `--force` when not needed.
- **Fix:** Always skip scenarios that already have `soap-note.json` / `medical-terms.json`. The `GenerateScenarioNotesCommand` has this guard built in (skips unless `--force`). Never use `--force` unless explicitly asked. This applies to any AI-generated content — check first, generate only what's missing.
- **Cost impact:** Each scenario costs ~2-3 min of Opus thinking time. 10 unnecessary regenerations = 20-30 min of wasted API calls at premium pricing.

## 2026-02-11

### Lesson 15: VPS agents create PRs from dirty branches, mixing unrelated changes
- **Bug:** PRs #68 and #69 were both supposed to be single-purpose (1-line CI fix and 1-line disclaimer fix respectively). Instead both had 10 files changed — DemoSeeder renames, auth interceptor fixes, DoctorDashboard field fixes, Pint formatting, lessons.md entries, and CLAUDE.md TODO updates leaked in.
- **Root cause:** VPS agents created branches from their long-lived workspace branches (`agent2-workspace`, `agent3-workspace`) instead of fresh `main`. The workspace had accumulated days of uncommitted/unstaged changes that got swept into the PR.
- **Fix:** Both PRs closed and recreated from clean `main` branches. Added mandatory workflow to CLAUDE.md PR Discipline section with explicit `git fetch origin main && git checkout -b fix/xyz FETCH_HEAD` pattern.
- **Takeaway:** Agents on VPS MUST always branch from fresh `main` for single-purpose PRs. `git diff --stat` before committing is non-negotiable. If more than the expected files appear — STOP and start over.

### Lesson 21: Agent worktree .env must match its Herd domain
- **Bug:** `postvisit-agent3.test` returned 401 on every request after login. "Sign in as Patient" called `demo/start` (200 OK) but subsequent API calls all failed with 401. Same codebase worked fine on `postvisit.test`.
- **Root cause:** The `.env` file in the agent3 worktree was copied from main and still had `APP_URL=http://postvisit.test` and `SANCTUM_STATEFUL_DOMAINS=postvisit.test`. Sanctum only attaches session cookies to stateful domains — requests from `postvisit-agent3.test` were treated as third-party/API and cookies were never sent.
- **Fix:** Updated `.env` in all agent worktrees (agent3, agent4, agent5) to use their correct Herd domain:
  ```
  APP_URL=http://postvisit-agentN.test
  SANCTUM_STATEFUL_DOMAINS=postvisit-agentN.test
  ```
- **Takeaway:** When creating a new git worktree for Herd, ALWAYS update `APP_URL`, `SANCTUM_STATEFUL_DOMAINS`, and `DB_DATABASE` in `.env` to match the worktree directory name. Herd serves each directory as `{dirname}.test` — if Sanctum config doesn't match, cookie auth silently fails.

### Lesson 22: Tests that call DemoSeeder block the entire test suite
- **Bug:** `herd php artisan test` hung indefinitely. Suite ran fine through DemoScenarioTest (8 tests, 0.3s) then froze — no output, no timeout, no error.
- **Root cause:** `DemoSeederTest` runs `$this->seed(DemoSeeder::class)` in `setUp()`. DemoSeeder calls `TermExtractor` which makes a real Anthropic API call. In test env without API key or with slow response, this blocks forever. Same issue in `BloodPressureMonitoringTest` (2 `@group slow` tests) and `ReferenceTest::test_demo_seeder_creates_references`.
- **Fix:** Deleted `DemoSeederTest.php` entirely (tests old seeder, replaced by DemoScenarioSeeder). Removed 2 slow tests from `BloodPressureMonitoringTest`. Removed 1 DemoSeeder test from `ReferenceTest`. Added PHPUnit time limits to `phpunit.xml` (10s small, 20s medium, 30s large, enforced).
- **Result:** 167 tests, 459 assertions, 3.90s total. Zero tests over 2s.
- **Takeaway:** NEVER call real AI services from tests. Any test calling DemoSeeder (which invokes TermExtractor) will block the suite. Use the new `DemoScenarioSeeder` which uses hardcoded config data. If a test takes >30s it must be deleted or rewritten — slow tests block all development.

### Lesson 23: Enforce PHPUnit time limits in phpunit.xml
- **Bug:** No test timeout configured — a single slow test could block the entire suite indefinitely with no feedback.
- **Fix:** Added `enforceTimeLimit="true"` with `timeoutForSmallTests="10"` / `timeoutForMediumTests="20"` / `timeoutForLargeTests="30"` to phpunit.xml.
- **Takeaway:** Always set PHPUnit time limits. 30 seconds max per test is the hard rule. Any test exceeding this is bad craftsmanship and must be rewritten.

### Lesson 24: Testing mobile features with Herd Expose (QR code upload, etc.)
- **Context:** QR code mobile upload bridge generates URLs pointing to `postvisit-agent2.test` — phones can't reach local `.test` domains. Need a public tunnel.
- **What works:** Herd has built-in **Expose** integration (free tier, no account needed beyond token in Herd settings).
- **Steps:**
  1. Open Herd → Settings → Expose → verify token is set (free tier OK)
  2. Temporarily change `APP_URL` in `.env` to the Expose public URL (so `url()` helper generates correct QR code URLs):
     ```
     APP_URL=https://RANDOM.sharedwithexpose.com
     ```
  3. Clear config cache: `herd php artisan config:clear`
  4. Start tunnel: `herd share` (from project directory)
  5. Expose prints public URL like `https://zwbybky2rz.sharedwithexpose.com`
  6. Test on phone — scan QR, upload photo, verify it appears on desktop
  7. **After testing — revert `APP_URL`** back to `http://postvisit-agent2.test` and clear cache again
- **What didn't work:**
  - `cloudflared tunnel` — Herd returned 404 because it didn't recognize the cloudflare Host header. `--http-host-header` flag fixed routing but Sanctum cookies broke ("session expired") because the cookie domain didn't match.
  - `herd share` via Expose works because Expose is designed for Herd — it handles Host headers and SSL correctly.
- **Free tier limits:** 60 minutes per session, random subdomain (no custom), EU Frankfurt server.
- **Takeaway:** For testing any feature that requires phone access (QR codes, mobile pages, push notifications), use `herd share`. Remember to update and revert `APP_URL` — otherwise QR code URLs will point to the wrong domain.
