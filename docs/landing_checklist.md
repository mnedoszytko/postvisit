# Landing Checklist — Evaluator Feedback Fixes

## 1. Early Validation / User Feedback
**Problem:** "No user research or patient feedback cited"

**Action:**
- Get 1-2 people (friend/family) to walk through the demo
- Collect 2-3 sentence testimonials from each
- Add "Early Validation" section to README.md with quotes
- Format: name (or initials), role (e.g. "patient perspective"), quote

## 2. PHI Encryption + Authorization Policies
**Problem:** "Consent/HIPAA aspirational, no PHI encryption"

**Action:**
- Create `VisitPolicy` — ownership check: patient can only access own visits, doctor only assigned patients
- Create `PatientPolicy` — ownership check: patient can only access own record
- Register policies in `AuthServiceProvider`
- Wire `authorize()` calls into controllers (VisitController, ChatController, etc.)
- Add Laravel `encrypted` cast to `Transcript.raw_transcript` and `Transcript.clean_transcript`
- Add `encrypted` cast to `VisitNote` sensitive fields (chief_complaint, history_of_present_illness, assessment, plan)
- Update README security section: "implemented" not "architecture-ready"

## 3. Physician Credibility
**Problem:** "Physician claim unverifiable"

**Action:**
- Add to README: short bio of Dr. Nedo with medical license number / specialty
- Or: link to LinkedIn / professional profile
- Goal: judges can verify medical credibility in 10 seconds
