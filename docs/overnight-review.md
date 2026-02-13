# Overnight Review -- PostVisit.ai
Date: 2026-02-12

## Executive Summary

PostVisit.ai is in very strong shape for a hackathon project with 4 days remaining. The patient-facing experience is polished, feature-rich, and clinically thoughtful. The AI chat with SSE streaming works well, the visit summary with expandable SOAP sections and tap-to-explain medical terms is a standout feature, and the health dashboard with vitals charts, lab results, and connected services is impressively comprehensive. The doctor panel provides a clean overview with alerts and patient drill-down. There are no critical blocking bugs. The main issues are cosmetic or data-related, with a few items that could confuse judges during a demo.

## Critical Issues (must fix before demo)

### 1. Model ID Mismatch in Settings (HIGH PRIORITY)
**File:** `app/Enums/AiTier.php` (line 31-35), `resources/js/components/AiTierSelector.vue` (line 84)
**Issue:** The Settings page shows "Opus 4.6" selected as the AI tier, but the model ID text underneath reads `claude-sonnet-4-6-20250929`. This happens because `ANTHROPIC_MODEL` env var is set to a sonnet model for dev cost savings, and the `model()` method in `AiTier.php` uses this override for ALL tiers.
**Impact:** A judge looking at the Settings page will see a contradiction -- "Opus 4.6" tier selected but a Sonnet model ID. This undermines the "Opus-only" narrative.
**Fix:** Either (a) remove the env override for the demo environment so Opus 4.6 shows `claude-opus-4-6`, or (b) display the tier's *intended* model rather than the runtime override, or (c) hide the model ID text entirely on the Settings page.

### 2. SpO2 Card Shows "%" Without a Value
**File:** `resources/js/components/health/VitalsTab.vue` (line 38-41)
**Issue:** On the Vitals tab, the "Avg SpO2" card renders as just `%` because `deviceData.blood_oxygen.average_spo2` is null/undefined while the parent `blood_oxygen` object exists (passing the `v-if`).
**Impact:** Looks like a rendering bug during demo. A judge will notice a card with just a percent sign and no number.
**Fix:** Either guard with `v-if="deviceData?.blood_oxygen?.average_spo2"` (more specific) or seed the demo data to include an SpO2 average value (e.g., 97).

## Visual/UX Issues

### 3. Document Filename "document (83).pdf" Looks Auto-Downloaded
**Page:** My Health > Documents tab
**Issue:** The only document is named `document (83).pdf` which looks like a browser auto-download filename, not a meaningful medical document.
**Impact:** Minor, but for a demo it would be more impressive to show something like "ECG_Report_Feb2026.pdf" or "Lab_Results_Cardiology.pdf".
**Suggestion:** Rename the demo document to something clinically meaningful during seeding.

### 4. Floating Chat Bubble Proximity to "Record New Visit" Button (Mobile)
**Page:** Patient Profile on mobile (375px)
**Issue:** The floating green chat bubble in the bottom-right overlaps/sits very close to the "Record New Visit" button.
**Impact:** On small screens, accidental taps could occur. Not critical since this is primarily a desktop demo.
**Suggestion:** Slight CSS adjustment -- either move the floating button higher when the CTA is visible, or hide it on the Profile page since there's already a global chat.

### 5. Audio Player Shows 0:00 / 0:00 Until Loaded
**Page:** Visit Summary, Visit Recording section
**Issue:** The HTML5 audio player shows `0:00 / 0:00` because `preload="none"` means metadata isn't fetched. The duration info from the transcript (`audio_duration_seconds`) is shown as a separate label above.
**Impact:** Very minor. The audio works when clicked. Users might wonder if it's broken before clicking play.
**Suggestion:** Change to `preload="metadata"` so the duration displays, or keep `preload="none"` and show a clearer "Click to play" state.

### 6. Visit Date Badge Shows Different Dates for Same Patient's Two Visits
**Page:** Patient Profile
**Issue:** Alex Johnson has visits on Feb 12 and Feb 11. This is intentional demo data, but during a demo walkthrough, having two visits on consecutive days might prompt questions. Not a bug -- just a demo data consideration.

## Code Quality Issues

### 7. v-html Usage in Multiple Components (XSS Consideration)
**Files:**
- `resources/js/components/ChatPanel.vue` (line 115) -- AI markdown rendering
- `resources/js/components/StreamingMessage.vue` (line 2) -- streaming AI response
- `resources/js/components/VisitSection.vue` (line 38) -- SOAP note rendering
- `resources/js/views/VisitView.vue` (lines 160, 196) -- recommendations/actions
- `resources/js/views/DoctorPatients.vue` (line 113) -- action icons

**Assessment:** All `v-html` usages are rendering either AI-generated markdown (from the backend, not user input) or controlled inline markdown (`inlineMd()` which only handles `**bold**`). The AI responses pass through `marked.parse()` which should sanitize by default with recent versions. The risk is LOW but for a healthcare application, adding DOMPurify as a sanitizer before `v-html` would be best practice. This is not blocking for a hackathon demo.

### 8. Empty Catch Blocks Throughout the Codebase
**Files:** 20+ occurrences across views and stores (Processing.vue, Feedback.vue, CompanionScribe.vue, MedicalLibrary.vue, AgentsPage.vue, DoctorPatientDetail.vue, DoctorAuditLog.vue, chat.js, settings.js, PatientLayout.vue, HealthDashboard.vue)

**Assessment:** Using `catch {}` (optional catch binding) silently swallows errors. While acceptable in many cases (e.g., "fetch counts, use 0 as fallback"), some of these could hide real issues during development. For production, at minimum add `console.error` in catch blocks for API calls. Not blocking for hackathon.

### 9. `useApi()` Called Inside Chat Store Action (Potential Timing Issue)
**File:** `resources/js/stores/chat.js` (line 42)
**Issue:** `useApi()` is called inside the `sendMessage` action rather than at store initialization. This works because `useApi` returns a shared axios instance, but the SSE streaming in `sendMessage` uses raw `fetch()` instead of axios anyway, making the `useApi()` call on line 42 unused dead code within that specific method.
**Impact:** No functional impact. The `fetch()` approach with manual CSRF handling is correct for SSE streaming. The `useApi()` call is only used in `fetchHistory`.

### 10. `formatDate` Function Defined But Unused in VisitView.vue
**File:** `resources/js/views/VisitView.vue` (line 630-633)
**Issue:** The `formatDate` function is defined in the `<script setup>` block but never used in the template -- the component uses `VisitDateBadge` instead.
**Impact:** Dead code. Minor cleanup opportunity.

### 11. `shortTitle` Function Defined But Unused in PatientProfile.vue
**File:** `resources/js/views/PatientProfile.vue` (line 218-222)
**Issue:** The `shortTitle` function is defined but never referenced in the template.
**Impact:** Dead code. Minor cleanup opportunity.

### 12. Watcher on `chatStore.messages` Uses `deep: true`
**File:** `resources/js/components/ChatPanel.vue` (line 794-796)
```javascript
watch(() => chatStore.messages, () => {
    scrollToBottom();
}, { deep: true });
```
**Assessment:** Deep watching the entire messages array could be expensive with many messages (up to MAX_MESSAGES = 100). Since Pinia already triggers reactivity on array mutations, a shallow watch might suffice. However, during streaming, the content property of individual messages is mutated in-place (e.g., `this.messages[aiIndex].content += parsed.text`), which does require deep watching. So this is actually correct and necessary for streaming updates.

### 13. MedicalLookupDemo Route Still Registered
**File:** `resources/js/router/index.js` (line 142)
**Issue:** `MedicalLookupDemo.vue` is still registered as a route at `/lookup-demo`. This appears to be a development/test view.
**Impact:** No harm, but could be confusing if a judge stumbles onto it. Consider removing from production routes.

## Performance Observations

### 14. Chat Clears on Mount
**File:** `resources/js/components/ChatPanel.vue` (line 864)
**Issue:** `onMounted` calls `chatStore.clearMessages()`. This means navigating between pages clears chat history each time the component mounts/unmounts. On the visit page this is fine (visit-specific chat), but the global chat also clears when navigating between Profile/Health/Library.
**Impact:** Users lose their chat conversation when switching tabs. This is probably intentional (each page provides different context), but could frustrate users who had an ongoing conversation.

### 15. Multiple API Calls on PatientProfile Mount
**File:** `resources/js/views/PatientProfile.vue` (lines 230-244)
**Issue:** The profile page fires 3 parallel API calls (`conditions`, `prescriptions`, `library`) just to populate counts for the quick links. These are small requests, but if the user bounces between pages, they refetch each time.
**Impact:** Negligible for a demo. For production, consider caching these counts in a store.

### 16. No Lazy Loading for Health Dashboard Tab Components
**File:** `resources/js/views/HealthDashboard.vue`
**Issue:** All five tab components (HealthProfileTab, VitalsTab, LabResultsTab, ConnectedServicesTab, DocumentsTab) are imported eagerly. Only the active tab is rendered (v-if), but all are bundled together.
**Impact:** Negligible for demo. For production, use dynamic `import()` for tab components.

## Suggestions for Demo Day

### Narrative Flow
1. **Start with the demo scenario picker** -- this is a great first impression. Show the 4 patient scenarios, click Alex Johnson.
2. **Visit Summary is the hero** -- expand SOAP sections, click a medical term to show the popover, then click "Ask AI" on a section. Let the streaming response impress judges.
3. **Health Dashboard** -- quickly show Vitals charts (blood pressure trend, heart rate), Lab Results with color-coded HIGH/LOW, Connected Services (4 of 14 connected).
4. **Reference Library** -- show PubMed-verified clinical guidelines auto-populated for the patient's conditions.
5. **Doctor Panel** -- switch roles, show the alert ("Weight gain 2.3 kg in 2 days"), then drill into a patient.
6. **Companion Scribe** -- show the recording interface but don't actually record (it needs real audio).

### Quick Wins Before Demo
- **Fix the model ID mismatch** (Critical Issue #1) -- this is the most important fix
- **Fix the SpO2 "%" display** (Critical Issue #2) -- easy data fix
- **Rename the demo document** from "document (83).pdf" to something meaningful
- **Consider hiding the "Good" and "Better" AI tiers** since the demo narrative is Opus-only
- **Pre-populate a chat conversation** or have ready-made questions to type during demo

### Strengths to Highlight
- **AI streaming with structured responses** -- the chat produces well-organized medical summaries with bold text, numbered lists, and section headers
- **Source citations** -- the AI shows where its information came from ("Your Visit Notes", "Dr. Chen - Feb 12")
- **Context-aware suggestions** -- the chat panel shows different suggestion buttons based on what page/section the user is viewing
- **Tap-to-explain medical terms** -- this is a unique and clinically valuable feature
- **Connected Services ecosystem** -- showing integration with Apple Health, Epic MyChart, CVS Pharmacy, Aetna
- **Doctor alerts** -- weight gain alert shows proactive monitoring

### Things to Avoid During Demo
- Don't show the Settings page unless asked (model ID mismatch)
- Don't click the SpO2 card on Vitals
- Don't expand the Documents tab unless you rename the file first
- Don't navigate too quickly between pages (chat clears on each navigation)

## Page-by-Page Notes

### Landing Page (`/`)
- Clean, focused design with "Try Demo" and "Sign In" buttons
- Disclaimer at bottom: "not a substitute for professional medical judgment"
- Mobile: renders well at 375px

### Login Page (`/login`)
- Demo shortcut section prominent: "TRY THE DEMO" with Patient and Doctor buttons
- OR SIGN IN divider with email/password form
- HTML5 validation works (tested empty submit)
- No console errors (only Chrome extension noise)

### Demo Scenario Picker (`/demo/scenarios`)
- 4 scenarios visible (Alex Johnson, Marie Dupont, Sofia Kowalska, Henri Lambert)
- Specialty filter pills at bottom (Cardiology, Endocrinology, Gastroenterology, Pulmonology)
- "Show 8 more scenarios" button reveals additional ones
- Language badges (EN/FR) on each card
- Disclaimer about fictional data at bottom
- Mobile: 2-column grid, works well

### Patient Profile (`/profile`)
- Patient avatar, name, age/gender, email
- Visit History with date badges, doctor photos, specialties
- "Record New Visit" CTA button (green with pulsing red dot)
- Health Record and Reference quick-link cards with counts
- "Ask" buttons on Visit History, Health Record, and Reference sections
- Chat panel opens on right (desktop) with relevant suggestions
- Mobile: hamburger menu, floating chat bubble

### Visit Summary (`/visits/:id`)
- Back to Profile link works
- Quick Summary card (green gradient)
- 6 SOAP sections with Expand/Collapse and Ask buttons (Chief Complaint, History of Present Illness, Reported Symptoms, Physical Examination, Assessment, Plan)
- Medical term highlighting (green underline) with tap-to-explain popover
- Doctor's Recommendations (numbered list)
- Attachments section
- AI-Extracted Clinical Entities with AI badge
- Visit Recording (audio player)
- Visit Transcript (collapsible)
- Chat panel on right with visit context, streaming works
- Source chips below AI responses
- "Powered by Opus 4.6" branding
- Copy, Print, Share actions on AI messages
- Mobile: tab switcher (Visit Summary / AI Chat) -- smart design

### My Health (`/health`)
#### Health Profile Tab
- Personal information (name, DOB, gender, phone, email, MRN)
- Biometrics (height, weight, BMI with color coding, blood type)
- Diagnoses with ICD code, severity, and status tags
- Recent visits
- Allergies (red tags)
- Emergency contact
- Edit Profile link

#### Vitals Tab
- Apple Watch Series 9 as data source with last sync time
- Key metrics cards (Resting HR, PVC Events, Steps, HRV, Sleep, SpO2)
- **BUG:** SpO2 shows "%" without value (see Critical Issue #2)
- Blood Pressure Trend (line chart, Systolic/Diastolic)
- Heart Rate Trend (area chart with baseline highlighting)
- Weight Trend (bar chart with average and change delta)
- Sleep Duration (stacked bar: Deep, REM, Light, Awake)
- Time range filter (7d, 30d, 90d, 1y)
- Ask AI buttons on each chart

#### Lab Results Tab
- Upload zone (drag & drop or browse)
- Time range filter
- Lab results list with color-coded status (HIGH/LOW/NORMAL)
- Reference ranges shown
- Ask AI button per result

#### Connected Services Tab
- 4 of 14 services connected
- HIPAA compliant badge
- Categories: Wearables, EHR Portals, Pharmacies, Labs, Insurance
- Connected services show sync timestamps
- Data type tags on each service

#### Documents Tab
- Upload zone
- 1 document listed (poorly named -- see Visual Issue #3)
- AI and Ask badges on document

### Reference Library (`/library`)
- Three tabs: Relevant for You, Search Databases, My Library
- Your Conditions section with ICD codes and "Ask AI" buttons
- Your Medications with dosage info
- Clinical References (8 PubMed-verified guidelines)
- Each reference shows authors, journal, year, verification badge, type tag, View source link
- Chat panel with "Context: reference"

### Settings (`/settings`)
- AI Intelligence Level selector (Good/Better/Opus 4.6)
- **BUG:** Model ID shows sonnet instead of opus (see Critical Issue #1)
- Agents & API card linking to `/agents`
- Data Governance toggles (Share docs, Allow AI analysis, Share with care team, FHIR export)
- Audit Logs table with PHI Access Tracking
- Legal links (Terms of Use, Privacy Policy, Legal Notice)

### AI Agents & API (`/agents`)
- Back to Settings link
- Base URL with copy button
- Authentication info (Bearer token)
- 8 API endpoints listed (GET/POST for health, visits, documents, chat)
- Clean two-column layout

### Companion Scribe (`/scribe`)
- Doctor selector with "+" add button
- Visit date field (pre-populated)
- Consent notice with Privacy Policy link
- Start Recording button
- No chat panel (correctly hidden on this page)

### Doctor Dashboard (`/doctor`)
- Sidebar navigation (Dashboard, Patients, Settings)
- "Requires Your Attention" alert (Weight gain for Alex Johnson)
- Stats cards: Patients (7), Unread Messages (0), Total Visits (9)
- Recent Patients list with BP and weight
- "Switch to Patient" button in demo banner

### Doctor Patients List (`/doctor/patients`)
- Search bar
- Patient table with condition, last vitals, visit count, last visit date
- Action buttons: Follow-up, Prescription, View
- Avatar fallback with initials for patients without photos

### Doctor Patient Detail (`/doctor/patients/:id`)
- Back to Patients link
- Patient header with conditions and AI session count
- Overview/Vitals/Labs tabs
- Weight Trend and Blood Pressure Trend charts
- Visit History
- AI Audit Trail (session count, message count, active/inactive status)
- Messages section

## Summary Metrics
- **Pages tested:** 15 (all major routes)
- **Critical bugs found:** 2
- **Visual/UX issues:** 4
- **Code quality issues:** 7
- **Performance observations:** 3
- **Console errors:** 0 (only Chrome extension noise)
- **Mobile responsiveness:** Good -- hamburger menu, tab switcher on visit page, floating chat bubble
- **AI chat:** Working, streaming responses render correctly with markdown formatting
