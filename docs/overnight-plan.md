# Overnight Run Plan — Night of Feb 10/11

> Agent: Claude Code (Opus 4.6)
> Started: ~22:00 CET
> Goal: Working product for morning testing

## Execution Order

### Phase 1: AI Works For Real (CRITICAL)
- [ ] Wire Anthropic SDK into ChatController — real AI responses with visit context
- [ ] Wire ExplainController — click medical term → Opus explains in plain language
- [ ] SSE streaming for chat and explain endpoints
- [ ] Test: demo login → visit view → click "?" → ask question → get real AI answer

### Phase 2: Feature Branches (parallel-safe)
- [ ] `feature/voice-chat` (POST-16) — MediaRecorder + Whisper STT + mic button in ChatPanel
- [ ] `feature/primevue` (POST-1) — PrimeVue 4 + Aura theme, replace custom components
- [ ] `feature/recording-animations` (POST-6) — 3 animation variants for review

### Phase 3: Medical APIs (POST-21)
- [ ] OpenFDA Drug Adverse Events client
- [ ] DailyMed Drug Labeling client
- [ ] NIH Clinical Tables client
- [ ] Wire into AI context for grounded answers
- [ ] Update docs/licenses.md

### Phase 4: Polish & Integration
- [ ] POST-10: Lab results section in VisitView (data already seeded)
- [ ] POST-13: Analyzing animation improvement + auto-redirect to visit view
- [ ] POST-12: README.md polish — structure, badges, sections (no screenshots yet)
- [ ] POST-11: Test STT with longer recording
- [ ] Resend email integration — install package, test sending, wire into Laravel mail

### Phase 5: Audit & Report
- [ ] Full security audit update (docs/security-audit.md)
- [ ] Propose next steps for Feb 11
- [ ] Update Linear issues status

## Rules
- Every commit: `herd php artisan test` + `bun run build` must pass
- Check `storage/logs/laravel.log` after each feature
- Safe changes → main, risky → feature branch
- Tag before large merges
