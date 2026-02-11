# Lessons Learned — log poprawek i błędów

Ten plik rejestruje błędy popełnione przez Claude i korekty od użytkownika.
Co 3-5 iteracji robimy rewizję i najważniejsze wnioski przenosimy do CLAUDE.md.

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

### Lesson 5: Enum values must match between migration and controller validation
- **Bug:** TranscriptController validated `source_type` as `ambient_device,uploaded_audio,manual_entry` but migration defines enum as `ambient_phone,ambient_device,manual_upload`
- **Fix:** Changed controller validation to match migration enum values
- **Takeaway:** Always check migration enum definitions when writing controller validation rules. Migration is the source of truth.

### Lesson 6: Auto-generate required identifiers in controllers
- **Bug:** VisitController::store did not set `fhir_encounter_id`, causing NOT NULL violation
- **Fix:** Auto-generate `fhir_encounter_id` as `'Encounter/' . Str::uuid()` in the controller
- **Takeaway:** When a model has required FHIR identifiers (fhir_*_id), auto-generate them if not provided by the client.

### Lesson 7: Set sensible defaults for NOT NULL columns without defaults
- **Bug:** TranscriptController::store did not set `stt_provider` or `audio_duration_seconds`, both NOT NULL in migration
- **Fix:** Default `stt_provider` to `'none'` and `audio_duration_seconds` to `0` when not provided
- **Takeaway:** Check all NOT NULL columns in migrations when building controllers — ensure every column has a value.

### Lesson 8: Field names must match between services and models
- **Bug:** `ScribeProcessor` used `$transcript->raw_text` and `VisitStructurer` used `$transcript->clean_text ?? $transcript->raw_text` — neither field exists on the Transcript model
- **Fix:** Changed both to `$transcript->raw_transcript` (the actual model field)
- **Takeaway:** Always verify model field names in the migration/model before referencing them in services. Run `grep -n fillable app/Models/ModelName.php` when unsure.

### Lesson 9: Sanctum TransientToken has no delete() method
- **Bug:** `AuthController::logout()` called `$request->user()->currentAccessToken()->delete()` which crashes with cookie-based SPA auth because Sanctum returns a `TransientToken` (not a `PersonalAccessToken`)
- **Fix:** Check `method_exists($token, 'delete')` before calling. Also invalidate session + regenerate CSRF for cookie auth.
- **Takeaway:** Sanctum SPA auth uses sessions, not tokens. Always handle both auth modes (token + session) in logout.

## 2026-02-11

### Lesson 10: Character offset validation is essential for AI-generated positional data
- **Issue:** AI models (even Opus 4.6) can produce slightly wrong character offsets when extracting medical terms from text. Off-by-one errors, Unicode miscounts, or hallucinated positions are common.
- **Solution:** Backend (`TermExtractor`) validates every offset by extracting the substring and comparing it to the claimed term (case-insensitive). Invalid offsets are dropped with a debug log. Frontend (`HighlightedText.vue`) also validates offsets client-side and falls back to string search if the offset doesn't match.
- **Takeaway:** Never trust AI-generated positional data without validation. Build defense in depth — validate on both backend and frontend.

### Lesson 11: Hardcode demo data offsets rather than relying on AI for demo reliability
- **Issue:** For the demo seeder, running AI extraction adds latency, cost, and a dependency on API availability during `db:seed`.
- **Solution:** 38 medical terms with manually verified character offsets are hardcoded in `DemoSeeder.php`. AI extraction (`TermExtractor`) is available for production use but not called during seeding.
- **Takeaway:** Demo data must be deterministic and self-contained. Use AI services for production flows, but hardcode critical demo data to ensure reliability during presentations.

### Lesson 12: Axios 401 interceptor can hijack non-auth routes
- **Bug:** The global axios response interceptor caught ALL 401 responses and force-redirected to `/login`. When `auth.init()` ran on the `/demo` page (which has no `requiresAuth` meta), the 401 from `GET /auth/user` caused a redirect to login — preventing the demo page from loading.
- **Fix:** Added `skipAuthRedirect` config flag to the interceptor. `auth.init()` now passes `{ skipAuthRedirect: true }` to the initial session check, so 401s during init don't trigger navigation.
- **Takeaway:** Global error interceptors must have escape hatches. Any interceptor that performs side effects (navigation, toasts) should check for opt-out flags.

### Lesson 13: Frontend template fields must match actual API response structure
- **Bug:** DoctorDashboard.vue used `dashboard.patient_count`, `dashboard.unread_messages`, `patient.name`, `patient.last_visit_date` — none of which exist in the API response. API returns `dashboard.stats.total_patients`, `dashboard.stats.unread_notifications`, `patient.first_name`/`patient.last_name`.
- **Fix:** Updated template to use correct field paths from the API response.
- **Takeaway:** When building frontend components, inspect the actual API response (or the controller return statement) before writing template bindings. Don't assume field names — verify them.

### Lesson 14: DoctorPatientDetail had same field mismatch pattern as DoctorDashboard
- **Bug:** DoctorPatientDetail.vue used `patient.name` (doesn't exist), `patient.conditions.join()` (conditions are objects, not strings → `[object Object]`), `patient.age` (doesn't exist), `visit.visit_date` (doesn't exist). Also `visit.visit_type` showed raw enum `office_visit`.
- **Fix:** Changed to `patient.first_name`/`patient.last_name`, computed `patientAge` from `patient.dob`, mapped `conditions` to `code_display`, formatted `visit.visit_type` → "Office Visit", used `visit.started_at` for date.
- **Takeaway:** Same lesson as #13 repeating. When any view renders model data, ALWAYS check the actual model fields (migration or `$fillable`) and the controller response. This error pattern is systematic — every new view needs a field audit.

### Lesson 15: VPS agents create PRs from dirty branches, mixing unrelated changes
- **Bug:** PRs #68 and #69 were both supposed to be single-purpose (1-line CI fix and 1-line disclaimer fix respectively). Instead both had 10 files changed — DemoSeeder renames, auth interceptor fixes, DoctorDashboard field fixes, Pint formatting, lessons.md entries, and CLAUDE.md TODO updates leaked in.
- **Root cause:** VPS agents created branches from their long-lived workspace branches (`agent2-workspace`, `agent3-workspace`) instead of fresh `main`. The workspace had accumulated days of uncommitted/unstaged changes that got swept into the PR.
- **Fix:** Both PRs closed and recreated from clean `main` branches. Added mandatory workflow to CLAUDE.md PR Discipline section with explicit `git fetch origin main && git checkout -b fix/xyz FETCH_HEAD` pattern.
- **Takeaway:** Agents on VPS MUST always branch from fresh `main` for single-purpose PRs. `git diff --stat` before committing is non-negotiable. If more than the expected files appear — STOP and start over.

### Lesson 16: MediaRecorder onstop fires asynchronously — never assume data is ready after .stop()
- **Bug:** `stopRecording()` called `mediaRecorder.stop()` and immediately set `step.value = 'done'`. But `onstop` (which saves the blob to `audioSegments`) fires async. User could click "Process Visit" before the final segment was saved — getting empty or incomplete data.
- **Fix:** `createRecorder()` now creates a Promise that resolves when `onstop` completes. `stopRecording()` awaits this promise before transitioning to the 'done' step.
- **Takeaway:** Browser media APIs are event-driven and asynchronous. After calling `.stop()` on MediaRecorder, the data is NOT ready until `onstop` fires. Always use Promises or callbacks to gate subsequent operations.

### Lesson 17: Shared mutable state + async callbacks = race condition (MediaRecorder chunk rotation)
- **Bug:** The chunk rotation feature used a shared `currentChunkData` array. When `rotateChunk()` stopped the old recorder and created a new one, the new recorder's `createRecorder()` cleared the shared array BEFORE the old recorder's async `onstop` could read it — losing the entire segment.
- **Fix:** Each `createRecorder()` call creates its own closure-scoped `chunkData` array. The old recorder's `onstop` reads its own closure data, unaffected by the new recorder.
- **Takeaway:** Never share mutable state between async producers. Each async producer should own its data via closures. This is the classic producer-consumer race condition.

### Lesson 18: Save audio to server BEFORE attempting transcription
- **Bug:** 21-minute recording (3 segments) was lost because the upload endpoint both stored and transcribed in one step. When transcription failed (MIME validation, then Whisper format rejection), the audio blob existed only in browser memory and was unrecoverable.
- **Fix:** 3-phase pipeline: (1) save all audio chunks to server via `save-chunk` endpoint, (2) transcribe each via `transcribe-chunk`, (3) combine text and submit. If any phase fails, audio files are already on disk.
- **Takeaway:** For irreplaceable user data (recordings, uploads), always persist to durable storage FIRST, then process. Never combine "save" and "process" into a single atomic operation when the data source is ephemeral (browser memory).

### Lesson 19: Always add beforeunload protection for in-progress recordings
- **Bug:** No warning when user accidentally closes tab during recording or upload. All audio data lost silently.
- **Fix:** Added `beforeunload` event listener that triggers browser's "are you sure?" dialog during recording or upload steps.
- **Takeaway:** Any page that holds ephemeral user data (recordings, unsaved forms, long-running uploads) MUST register a `beforeunload` handler. Remove it on unmount.

### Lesson 20: Retry should reuse resources, not create new ones
- **Bug:** If upload failed mid-way, clicking "Process Visit" again created a NEW visit, orphaning the old one (which already had audio chunks saved to it).
- **Fix:** `createdVisitId` is persisted across retries. If a visit was already created, the retry reuses it.
- **Takeaway:** When implementing retry flows for multi-step operations, persist intermediate resource IDs so retries are idempotent. Don't create new parent resources on retry.

## 2026-02-11

### Lesson 21: Agent worktree .env must match its Herd domain
- **Bug:** `postvisit-agent3.test` returned 401 on every request after login. "Sign in as Patient" called `demo/start` (200 OK) but subsequent API calls all failed with 401. Same codebase worked fine on `postvisit.test`.
- **Root cause:** The `.env` file in the agent3 worktree was copied from main and still had `APP_URL=http://postvisit.test` and `SANCTUM_STATEFUL_DOMAINS=postvisit.test`. Sanctum only attaches session cookies to stateful domains — requests from `postvisit-agent3.test` were treated as third-party/API and cookies were never sent.
- **Fix:** Updated `.env` in all agent worktrees (agent3, agent4, agent5) to use their correct Herd domain:
  ```
  APP_URL=http://postvisit-agentN.test
  SANCTUM_STATEFUL_DOMAINS=postvisit-agentN.test
  ```
- **Takeaway:** When creating a new git worktree for Herd, ALWAYS update `APP_URL`, `SANCTUM_STATEFUL_DOMAINS`, and `DB_DATABASE` in `.env` to match the worktree directory name. Herd serves each directory as `{dirname}.test` — if Sanctum config doesn't match, cookie auth silently fails.

### Lesson 22: Deduplicate 401 toast notifications
- **Bug:** When session expires, pages with multiple parallel API calls (e.g., HealthDashboard making 3 requests) show N identical "Session expired" toasts stacked on top of each other.
- **Fix:** Added a 5-second timestamp-based cooldown in the axios 401 interceptor. After showing one toast and redirecting to login, subsequent 401s within 5 seconds are silently rejected.
- **Takeaway:** Any global error handler (toast, redirect) that can be triggered by parallel requests needs deduplication. Timer-based cooldown is more robust than flag-based (flags reset too fast with async router navigation).
