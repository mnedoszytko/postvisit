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

### Lesson 8: Sanctum TransientToken has no delete() method
- **Bug:** `AuthController::logout()` called `$request->user()->currentAccessToken()->delete()` which crashes with cookie-based SPA auth because Sanctum returns a `TransientToken` (not a `PersonalAccessToken`)
- **Fix:** Check `method_exists($token, 'delete')` before calling. Also invalidate session + regenerate CSRF for cookie auth.
- **Takeaway:** Sanctum SPA auth uses sessions, not tokens. Always handle both auth modes (token + session) in logout.

## 2026-02-11

### Lesson 9: Character offset validation is essential for AI-generated positional data
- **Issue:** AI models (even Opus 4.6) can produce slightly wrong character offsets when extracting medical terms from text. Off-by-one errors, Unicode miscounts, or hallucinated positions are common.
- **Solution:** Backend (`TermExtractor`) validates every offset by extracting the substring and comparing it to the claimed term (case-insensitive). Invalid offsets are dropped with a debug log. Frontend (`HighlightedText.vue`) also validates offsets client-side and falls back to string search if the offset doesn't match.
- **Takeaway:** Never trust AI-generated positional data without validation. Build defense in depth — validate on both backend and frontend.

### Lesson 10: Hardcode demo data offsets rather than relying on AI for demo reliability
- **Issue:** For the demo seeder, running AI extraction adds latency, cost, and a dependency on API availability during `db:seed`.
- **Solution:** 38 medical terms with manually verified character offsets are hardcoded in `DemoSeeder.php`. AI extraction (`TermExtractor`) is available for production use but not called during seeding.
- **Takeaway:** Demo data must be deterministic and self-contained. Use AI services for production flows, but hardcode critical demo data to ensure reliability during presentations.
