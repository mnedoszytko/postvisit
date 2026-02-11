# Lessons Learned — log poprawek i błędów

Ten plik rejestruje błędy popełnione przez Claude i korekty od użytkownika.
Co 3-5 iteracji robimy rewizję i najważniejsze wnioski przenosimy do CLAUDE.md.

**Last promotion:** 2026-02-11 — 16 lessons promoted to CLAUDE.md (Field Audit Rule, Ephemeral Data Protection, AI Output Validation, Demo Seeder Completeness, Axios Interceptor Safety).

## 2026-02-10

### Lesson 1: Nazwa projektu MedDuties
- **Błąd:** Claude szukał projektu "MedNutis" — błędna nazwa
- **Korekta:** Projekt nazywa się **MedDuties** (katalog `../dyzury`)
- **Wniosek:** Gdy user podaje nazwę projektu, dopytaj jeśli nie znajdziesz — nie zgaduj

### Lesson 2: Nie przenoś założeń z innych projektów
- **Błąd:** CLAUDE.md zawierał "MySQL, Redis, PHP 8.2+" — skopiowane z PreVisit bez pytania
- **Korekta:** PHP 8.4 (bez dyskusji), baza danych i cache do ustalenia
- **Wniosek:** Każdy projekt ma własne założenia. Nie kopiuj stacku z siostrzanych projektów — pytaj albo oznacz jako TBD

### Lesson 3: Informuj o postępie
- **Błąd:** Nie podawałem numeru sekcji w kontekście całości (np. "sekcja 4" bez "z 11")
- **Korekta:** Zawsze mów "sekcja X z Y" żeby user wiedział na jakim etapie jest
- **Wniosek:** Przy iteracyjnej pracy sekcja po sekcji, zawsze dawaj kontekst postępu

### Lesson 4: Mniejsze porcje do review
- **Błąd:** Sekcja 11 (Out of Scope) przedstawiona jako wielki blok tekstu — user nie był w stanie tego przeczytać
- **Korekta:** Prezentuj mniejszymi cząstkami, daj czas na przeczytanie każdej
- **Wniosek:** Przy prezentowaniu treści do review — max 1 tabelka lub 5-8 punktów na raz. Lepiej 3 krótkie wiadomości niż 1 ściana tekstu

### Lesson 9: Sanctum TransientToken has no delete() method
- **Bug:** `AuthController::logout()` called `$request->user()->currentAccessToken()->delete()` which crashes with cookie-based SPA auth because Sanctum returns a `TransientToken` (not a `PersonalAccessToken`)
- **Fix:** Check `method_exists($token, 'delete')` before calling. Also invalidate session + regenerate CSRF for cookie auth.
- **Takeaway:** Sanctum SPA auth uses sessions, not tokens. Always handle both auth modes (token + session) in logout.

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
