# Pre-Landing Checklist — Feb 16 (pre-submission)

Verify all features are properly exposed and polished before demo launch.

Deadline: **15:00 EST**

---

## Done
- [x] Early Validation — 3 testimonials (Valdimar, Marta, Dr. Borkowski)
- [x] PHI Encryption — encrypted casts on Transcript + VisitNote SOAP fields
- [x] Authorization Policies — VisitPolicy + PatientPolicy with ownership checks
- [x] Physician Credibility — Dr. Nedoszytko bio, PWZ, Belgian license, LinkedIn
- [x] Trim Overdocumentation — 26% → 18% markdown ratio (13 internal docs removed)

---

## Pre-Landing — features exposed & polished

### 1. Brand presence
- [ ] PostVisit.ai brand voice in loading states ("PostVisit is analyzing your documents..." etc.) (POST-91)
- [ ] Demo video uploaded and embedded in Landing.vue
- [ ] Video works on desktop and mobile

### 2. Demo polish — nothing forgotten
- [ ] All key features visible in demo flow without digging
- [ ] Medical term highlighting works and is obvious
- [ ] AI chat suggestions cover common patient questions
- [ ] Doctor dashboard shows alerts and patient overview
- [ ] Health dashboard charts render with demo data
- [ ] Medication interactions visible
- [ ] Document AI analysis auto-refreshes (no manual reload)

---

## Landing — deploy & go live

### 3. Production — deployed, secured, working
- [ ] Deployed on Forge, HTTPS enforced, `APP_DEBUG=false`
- [ ] Rate limiting on public endpoints
- [ ] Bot protection active (Cloudflare WAF, `robots.txt`)
- [ ] No debug/stack traces leaking in error responses
- [ ] Remove IP whitelist / access restrictions — publicly accessible for judges

### 4. Server — performance, data freshness
- [ ] Pages load fast, AI chat streams within 2s
- [ ] Demo data seeded and up to date
- [ ] No 500 errors in logs
- [ ] Queue worker running

### 5. Cleanup — no dev artifacts, no doc duplicates
- [ ] Remove temp scripts, scratch files, agent worktree leftovers
- [ ] No `console.log()` / `dd()` / `dump()` in production code
- [ ] No duplicate or outdated docs
- [ ] README concise and scannable
- [ ] Markdown ratio < 20%

### 6. Smoke test — full judge flow
- [ ] Try Demo → scenario → visit summary → tap term → AI chat → doctor dashboard

### 7. Offline installation (Docker)
- [ ] Verify `docker compose up` works from clean clone
- [ ] App runs and demo flow works in Docker environment
