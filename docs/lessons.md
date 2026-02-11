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
- **Bug:** PRs #68 and #69 were supposed to be single-purpose (1-line CI fix, 1-line disclaimer fix). Instead both contained 10 changed files — DemoSeeder renames, auth interceptor fixes, dashboard field mapping, Pint formatting, lessons.md entries, CLAUDE.md TODO updates. None of these belonged in those PRs.
- **Root cause:** VPS agents created branches from their long-lived workspace branches (e.g. `agent4-workspace`) instead of fresh `main`. The workspace had accumulated days of uncommitted/unstaged changes. When the agent ran `git checkout -b fix/something`, all workspace drift came along.
- **Fix:** Both PRs closed and recreated from clean `main` branches. Added mandatory workflow to CLAUDE.md PR Discipline section with explicit `git checkout main && git pull` before branching.
- **Takeaway:** Agents on VPS MUST always branch from fresh `main` for single-purpose PRs. Never branch from a workspace branch. Always verify `git diff --stat` before committing — if unexpected files appear, the branch is dirty. Delete and start over.
