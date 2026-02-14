# Flight Checklist — SFO (11h, offline)

Deadline: **Feb 16, 15:00 EST** (niedziela). Landing: sobota rano PST.

---

## Priority 1: AI Prompts Documentation (2-3h)

`docs/ai-prompts.md` documents only 1 of 12 prompts (term-extractor). Judges care about this.

For each file in `prompts/`, add to `docs/ai-prompts.md`:
- Purpose (1 sentence)
- Input context (what data goes in)
- Output format (what AI returns)
- Key guardrails

Files to document:
- [ ] `qa-assistant.md` — main patient chat
- [ ] `qa-assistant-quick.md` — quick mode variant
- [ ] `medical-explainer.md` — term explanations
- [ ] `scribe-processor.md` — transcript → SOAP
- [ ] `visit-summarizer.md` — visit summary generation
- [ ] `visit-structurer.md` — structuring raw notes
- [ ] `escalation-detector.md` — safety guardrail
- [ ] `meds-analyzer.md` — medication analysis
- [ ] `document-analyzer.md` — uploaded doc analysis
- [ ] `library-item-analyzer.md` — reference analysis
- [ ] `context-guidelines.md` — context assembly rules

All files are local in `prompts/` — no internet needed.

---

## Priority 2: Demo Video Script (2-3h)

Demo video = **30% of hackathon score**. Max 3 minutes. Plan every second.

### Script structure:
1. **Problem** (20s) — patient leaves doctor, forgets everything
2. **Solution overview** (15s) — AI assistant with full visit context
3. **Patient flow** (60s):
   - Scenario picker → Alex Johnson PVCs
   - Quick Summary with highlighted terms (tap → definition popup)
   - SOAP sections with term expansion
   - Ask AI on any section → contextual chat
   - Chat with sources, streaming, Opus 4.6
4. **Health Dashboard** (30s):
   - Vitals from Apple Watch, lab results with charts
   - Document upload → AI analysis
   - Medical Library with clinical references
5. **Doctor flow** (20s):
   - Patient list, visit review, reply to patient
6. **Tech highlights** (15s):
   - Opus 4.6, 200K context, streaming SSE, 12 specialized prompts
7. **Close** (10s)

### Key wow moments to capture:
- Term highlighting → tap → patient-friendly definition → "Ask more in chat"
- Chat streaming with source chips
- Doctor photo + practitioner context
- Voice recording → transcription → SOAP note pipeline

---

## Priority 3: ATTRIBUTION.md (30min)

Create `ATTRIBUTION.md` in repo root:

```markdown
# Attribution

## Clinical Content
- WikiDoc medical articles — CC-BY-SA 3.0
- DailyMed drug labels — Public Domain (NLM/NIH)
- WHO cardiovascular guidelines — CC-BY 3.0 IGO

## AI Services
- Anthropic Claude Opus 4.6 — external API (not bundled)
- OpenAI Whisper — external API (not bundled)

## Open Source
See docs/licenses.md for full dependency audit.
```

---

## Priority 4: Documentation Polish (1-2h)

- [ ] Review `README.md` — does setup guide work from scratch?
- [ ] Review `CHANGELOG.md` — add missing entries from Feb 13-14
- [ ] Review all `docs/*.md` for Polish language remnants → translate to English
- [ ] Draft 100-200 word submission summary (English)
- [ ] Verify `docs/demo-guide.md` matches current UI

---

## Priority 5: Code Review Notes (1h)

Read through and note issues (fix after landing):
- [ ] Scan for unused Vue components (dead code)
- [ ] Check unused routes in `routes/api.php`
- [ ] Review open PRs (#151-154) — decide merge/close
- [ ] Note any UI inconsistencies from memory

---

## Post-Landing Immediate (requires internet)

```bash
# 1. Pull latest, verify
git pull origin main
bun run build
herd php artisan test
./vendor/bin/pint --dirty

# 2. Commit flight work
git add docs/ ATTRIBUTION.md
git commit -m "Pre-submission documentation polish"
git push

# 3. Final checks
herd php artisan config:clear
# Test full demo flow in browser

# 4. Record demo video (Saturday afternoon)
# 5. Upload to YouTube (unlisted) or Loom
# 6. Make repo public
# 7. Submit on platform: repo + video + summary
```

---

## Status Before Flight

| Area | Status | Flight Work |
|------|--------|-------------|
| Code & Tests | Done | - |
| API docs | Done | - |
| Architecture docs | Done | - |
| Demo guide | Done | Review |
| AI prompts docs | **8% done** | **Write** |
| CHANGELOG | Done | Update |
| ATTRIBUTION | Missing | **Create** |
| Demo video | Not started | **Script** |
| Submission text | Not started | **Draft** |

Everything needed is already on your laptop. No internet required.
