# Changelog

All notable changes to PostVisit.ai are documented here in reverse chronological order.

## 2026-02-11

### Added
- **Medical Lookup API (POST-21)** — New `/api/v1/lookup/` endpoints exposing NIH Clinical Tables and DailyMed APIs for ICD-10 conditions, drugs, procedures, and drug labels. Includes validation, auth guards, and graceful error handling for external API failures.
- **Registration form (POST-19)** — Full registration flow with role selection (patient/doctor), password confirmation, field validation with server-side error display, and "Sign up" link from login page. Auto-authenticates on successful registration.
- **Medical term highlighting (tap-to-explain)** — Medical terms in SOAP notes are now highlighted and clickable. Tapping a term opens the ChatPanel with "Explain: {term}" pre-filled, triggering a contextual AI explanation. Implements PRD user story P3.
  - New `medical_terms` jsonb column on `visit_notes` table storing extracted terms with character offsets per SOAP section
  - `TermExtractor` AI service (`app/Services/AI/TermExtractor.php`) for production term extraction with offset validation and string-search fallback
  - `term-extractor` system prompt (`prompts/term-extractor.md`) with thorough extraction (8-20 terms/section), category examples, and causal-relationship safety rule
  - `HighlightedText.vue` component rendering clickable terms with client-side offset validation and string-search fallback
  - `VisitSection.vue` updated to conditionally render highlighted text when terms are available
  - `VisitView.vue` updated to pass terms data and handle `explain-term` events
  - Demo seeder includes 38 hardcoded medical terms with verified character offsets across 6 SOAP sections
  - `visits:reextract-terms` artisan command for re-extracting terms on existing visits (`--visit=<id>`, `--missing`, `--all`)
- **Audio chunking for long recordings** — MediaRecorder rotates every 10 minutes to stay under Whisper's 25 MB limit. Chunks are saved, transcribed, and combined server-side.
- **Save-first-then-transcribe pattern** — All audio is uploaded to server before transcription begins. Prevents data loss if Whisper fails or browser closes.
  - `POST /visits/{visit}/transcript/save-chunk` — saves audio without transcribing
  - `POST /visits/{visit}/transcript/transcribe-chunk` — transcribes and keeps file on disk
- **Visit info form on Companion Scribe** — Doctor selection dropdown, date picker, and specialty badge shown before recording starts
  - `GET /practitioners` endpoint returns all practitioners
  - Button disabled until doctor is selected
  - Auto-selects if only one practitioner exists
- **Demo mode on Companion Scribe** — `?demo=true` query parameter shows "Use Demo Recording" button that creates a visit with the full 26-minute cardiology transcript loaded server-side
- **Sync transcript processing** — `/transcript/process?sync=true` parameter for synchronous AI processing without queue worker, creates VisitNote from SOAP output
- **Doctor patient detail messaging** — Chat sessions with expandable message threads, notification display with unread badges, and inline reply functionality
- **Processing screen elapsed timer** — Shows elapsed time with "usually takes about a minute" hint
- **Context-aware "Explain this"** — Chat panel shows context badge and section-specific suggestion buttons instead of generic questions
- **Chat highlight animation** — Panel flashes emerald border when switching context between sections

### Fixed
- **Recording pipeline hardened** — Fixed `stopRecording` race condition where `onstop` fires asynchronously but step transition was immediate; user could click "Process Visit" before final blob was saved. Now awaits `onstop` promise.
- **MediaRecorder chunk rotation race condition** — Each recorder now uses closure-scoped data array instead of shared variable, preventing data loss during 10-minute segment rotation
- **beforeunload warning** — Browser warns before closing tab during recording or upload
- **Retry reuses visit** — If upload fails, clicking "Process Visit" again reuses the same visit instead of creating an orphaned one
- **TermExtractor offset validation** — AI-generated offsets validated with string-search fallback via `stripos()`. Definitions preserved through validation pipeline.
- **MIME validation removed from chunk upload** — Strict `mimes:...` validation rejected valid MediaRecorder blobs; replaced with `file` + `max:102400`
- **Whisper language detection** — Removed hardcoded `language='en'` from WhisperProvider, allowing auto-detection for multilingual recordings
- URL redirect handling during Chrome testing
- Dosage format display issues
- Markdown rendering in chat responses

### Changed
- **Processing view uses real status polling** — Replaced fake timer-based progress animation with actual API status polling (`/transcript/status`). Steps advance based on real entity extraction and SOAP note completion
- **Dashboard stats mapping fixed** — Doctor dashboard now correctly maps `total_patients` to patient count display. Patient names use null-safe concatenation
- **DemoSeeder uses real transcript** — Loads actual 26-minute cardiology visit from `demo/transcript.txt` instead of placeholder text

### Removed
- `DemoMode.vue` standalone page — demo functionality consolidated into CompanionScribe via `?demo=true`
- `/demo` route from Vue Router

### Improved
- Processing view with step-by-step progress indicators and auto-redirect on completion
- TermExtractor now produces 50-60 terms across all SOAP sections (up from 5), with definitions for each term
- Recording pipeline: 3-phase upload (save → transcribe → combine) for maximum data safety

## 2026-02-10

### Added
- Initial project scaffold: Laravel 12 + Vue 3 integrated SPA
- Full data model (22 migrations, 18 models, all UUID-based, FHIR R4 aligned)
- 45 API endpoints across auth, patients, visits, transcripts, chat, explain, medications, doctor dashboard, audit, and demo
- AI service layer (10 classes) with SSE streaming for chat and explain
- Sanctum cookie-based SPA authentication with role middleware
- RxNorm medication search and drug interaction checking
- Doctor dashboard with patient engagement stats and chat audit trail
- Demo seeder with realistic cardiology visit scenario (PVCs, propranolol)
- Resend email integration with visit summary
- 67 feature tests, 175 assertions
