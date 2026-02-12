# Landing Checklist — Feb 16 (pre-submission)

Deadline: **15:00 EST**

---

## Done
- [x] Early Validation — 3 testimonials (Valdimar, Marta, Dr. Borkowski)
- [x] PHI Encryption — encrypted casts on Transcript + VisitNote SOAP fields
- [x] Authorization Policies — VisitPolicy + PatientPolicy with ownership checks
- [x] Physician Credibility — Dr. Nedoszytko bio, PWZ, Belgian license, LinkedIn
- [x] Trim Overdocumentation — 26% → 18% markdown ratio (13 internal docs removed)

---

## TODO

### 1. Production — deployed, secured, working
- [ ] Deployed on Forge, HTTPS enforced, `APP_DEBUG=false`
- [ ] Rate limiting on public endpoints
- [ ] Bot protection active (Cloudflare WAF, `robots.txt`)
- [ ] No debug/stack traces leaking in error responses
- [ ] Remove IP whitelist / access restrictions — site must be publicly accessible for judges
- [ ] Demo flow works end-to-end for a first-time juror

### 2. Server — performance, data freshness
- [ ] Pages load fast, AI chat streams within 2s
- [ ] Demo data seeded and up to date
- [ ] No 500 errors in logs
- [ ] Queue worker running

### 3. Cleanup — no dev artifacts, no doc duplicates
- [ ] Remove temp scripts, scratch files, agent worktree leftovers
- [ ] No `console.log()` / `dd()` / `dump()` in production code
- [ ] No duplicate or outdated docs
- [ ] README concise and scannable
- [ ] Markdown ratio < 20%

### 4. Demo video on landing page
- [ ] Video uploaded and embedded in Landing.vue
- [ ] Works on desktop and mobile

### 5. Smoke test — full judge flow
- [ ] Try Demo → scenario → visit summary → tap term → AI chat → doctor dashboard

### 6. Offline installation (Docker)
- [ ] Verify `docker compose up` works from clean clone
- [ ] App runs and demo flow works in Docker environment
