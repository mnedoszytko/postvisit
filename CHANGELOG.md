# Changelog

All notable changes to PostVisit.ai are documented here in reverse chronological order.

## 2026-02-11

### Added
- **Medical term highlighting (tap-to-explain)** — Medical terms in SOAP notes are now highlighted and clickable. Tapping a term opens the ChatPanel with "Explain: {term}" pre-filled, triggering a contextual AI explanation. Implements PRD user story P3.
  - New `medical_terms` jsonb column on `visit_notes` table storing extracted terms with character offsets per SOAP section
  - `TermExtractor` AI service (`app/Services/AI/TermExtractor.php`) for production term extraction with offset validation
  - `term-extractor` system prompt (`prompts/term-extractor.md`) defining extraction rules and output format
  - `HighlightedText.vue` component rendering clickable terms with client-side offset validation and string-search fallback
  - `VisitSection.vue` updated to conditionally render highlighted text when terms are available
  - `VisitView.vue` updated to pass terms data and handle `explain-term` events
  - Demo seeder includes 38 hardcoded medical terms with verified character offsets across 6 SOAP sections
- **Demo mode on Companion Scribe** — `?demo=true` query parameter shows "Use Demo Recording" button that creates a visit with the full 26-minute cardiology transcript loaded server-side
- **Sync transcript processing** — `/transcript/process?sync=true` parameter for synchronous AI processing without queue worker, creates VisitNote from SOAP output
- **Doctor patient detail messaging** — Chat sessions with expandable message threads, notification display with unread badges, and inline reply functionality

### Fixed
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
