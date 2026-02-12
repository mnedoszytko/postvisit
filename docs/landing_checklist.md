# Landing Checklist — Monday Feb 16 (pre-submission)

Final checks before hackathon submission deadline (15:00 EST).

## Done (PR #140)
- [x] Early Validation — 3 testimonials in README (Valdimar, Marta, Dr. Borkowski)
- [x] PHI Encryption — encrypted casts on Transcript + VisitNote SOAP fields
- [x] Authorization Policies — VisitPolicy + PatientPolicy with ownership checks
- [x] Physician Credibility — Dr. Nedoszytko bio, PWZ, Belgian license, LinkedIn

## Done (PR #139)
- [x] Trim Overdocumentation — 26% → 18% markdown ratio (13 internal docs removed)

---

## TODO Monday

### 1. Production Security & Bot Protection
- [ ] Rate limiting on all public endpoints (demo/start, auth/login)
- [ ] Verify Cloudflare WAF rules are active
- [ ] Test that demo flow works without friction for a first-time juror
- [ ] Check no debug/stack traces leak in production error responses
- [ ] Verify HTTPS enforced, no mixed content warnings

### 2. Demo Video on Landing Page
- [ ] Upload demo video to hosting (YouTube unlisted or direct)
- [ ] Embed video on landing page (Landing.vue)
- [ ] Verify autoplay/controls work on desktop and mobile
- [ ] Add fallback image/poster if video doesn't load

### 3. Documentation Balance Check
- [ ] Re-run evaluator — confirm markdown ratio < 20%
- [ ] Verify no orphaned links in README (all docs/ references point to existing files)
- [ ] Skim each remaining doc for bloat — trim if verbose
- [ ] Ensure README is concise and scannable (judges have limited time)

### 4. Smoke Test — Full Judge Flow
- [ ] Open postvisit.ai in incognito browser
- [ ] Click Try Demo → pick scenario → see visit summary
- [ ] Tap medical term → explanation loads
- [ ] Ask AI question → streaming response works
- [ ] Check medications tab → interactions show
- [ ] Switch to doctor dashboard → alerts visible
- [ ] QR upload → generates code (don't need to test phone)
