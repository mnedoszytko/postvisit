# AI Prompts Documentation

This document catalogs all AI system prompts used in PostVisit.ai. Prompts are stored as versioned files in the `prompts/` directory and loaded via the `PromptLoader` service.

PostVisit uses 14 prompt files across 5 categories. Each prompt is loaded at runtime by its corresponding service class, never hardcoded in controllers.

---

## Table of Contents

**Patient-Facing**
- [qa-assistant](#qa-assistant) -- Post-visit chat (full context)
- [qa-assistant-quick](#qa-assistant-quick) -- Instant first response
- [medical-explainer](#medical-explainer) -- Tap-to-explain terms
- [patient-education](#patient-education) -- Comprehensive health guide with tool use
- [visit-summarizer](#visit-summarizer) -- Patient-friendly visit summary

**Clinical Processing**
- [scribe-processor](#scribe-processor) -- Transcript to structured SOAP
- [visit-structurer](#visit-structurer) -- Structured visit sections
- [term-extractor](#term-extractor) -- Medical term extraction with offsets
- [context-guidelines](#context-guidelines) -- Clinical guidelines formatting template

**Safety**
- [escalation-detector](#escalation-detector) -- Urgent symptom detection

**Doctor Tools**
- [doctor-inquiry](#doctor-inquiry) -- AI-assisted patient message analysis
- [meds-analyzer](#meds-analyzer) -- Medication analysis and interactions

**Document Analysis**
- [document-analyzer](#document-analyzer) -- Clinical document/image analysis
- [library-item-analyzer](#library-item-analyzer) -- Personal library document analysis

---

## Patient-Facing Prompts

### qa-assistant

**File:** `prompts/qa-assistant.md`
**Service:** `App\Services\AI\QaAssistant`
**Model:** Opus 4.6 (production), Sonnet (Good tier)

#### Purpose

The primary patient chat prompt. Powers the conversational Q&A interface where patients ask questions about their visit after leaving the doctor's office. This is the most-used prompt in the system and the core of the PostVisit experience.

#### Input Context

Assembled by `ContextAssembler::assembleForVisit()` with up to 8 data layers:

1. **System prompt** (this file, cached via prompt caching)
2. **Clinical guidelines** (ESC/AHA, cached, Opus 4.6 tier only)
3. **Visit data** -- SOAP note, transcript, observations, practitioner info
4. **Patient record** -- demographics, conditions, allergies, medications
5. **Health history** -- 3 months of observations across all visits, with trends
6. **Recent visit summaries** -- previous visits (all on Opus 4.6, last 3 on lower tiers)
7. **Wearable device data** -- Apple Watch heart rate, HRV, activity, sleep
8. **FDA safety data** -- adverse events, drug labels, interaction warnings
9. **Personal library** -- patient-uploaded documents with AI analysis
10. **Context compaction** -- previous session summaries (opt-in)

Conversation history is appended after the context layers.

#### Expected Output

Streaming markdown text response with:
- Clear, simple language (8th grade reading level)
- Bold key terms on first use with explanations
- Source attribution block at the end (`[sources]...[/sources]`)
- 2-4 paragraph typical length

#### Key Design Decisions

- **Never diagnose or prescribe** -- the prompt explicitly forbids issuing new diagnoses or suggesting medication changes. The AI explains what the doctor said, never overrides it.
- **Escalation protocol** -- hardcoded list of critical symptoms (chest pain, breathing difficulty, suicidal ideation) triggers immediate emergency response, bypassing normal conversation.
- **No repetition commentary** -- the prompt instructs the AI to never comment on how many times a patient has asked about a topic. Patients revisit questions for reassurance, and meta-commentary ("as I mentioned before") undermines trust.
- **Source attribution is mandatory** -- every response must include a `[sources]` block listing which data layers were used. The frontend parses this into visual source badges.
- **Adaptive thinking budget** -- the `QaAssistant` classifies each question as low/medium/high/max effort using keyword heuristics, then allocates extended thinking tokens accordingly (1K for simple lookups, up to 16K for safety-critical questions on Opus 4.6).
- **Clinical Reasoning Pipeline** -- complex drug/safety questions trigger the `ClinicalReasoningPipeline` (Plan-Execute-Verify pattern) instead of the standard single-pass path.

#### Max Tokens

Variable by tier and effort: 2,048 (low, Opus 4.6) up to 32,000 (max, Opus 4.6).

---

### qa-assistant-quick

**File:** `prompts/qa-assistant-quick.md`
**Service:** `App\Services\AI\QaAssistant::quickAnswer()`
**Model:** Claude Haiku 4.5 (always, regardless of tier)

#### Purpose

Provides an instant 2-3 sentence acknowledgment while the full Opus 4.6 response is being prepared. This is the "fast lane" that eliminates perceived latency -- the patient sees a brief response within 1-2 seconds while the deep analysis runs in the background.

#### Input Context

Minimal context via `ContextAssembler::assembleQuickContext()`:
- Visit data (SOAP note, practitioner, transcript)
- Patient record (conditions, medications, allergies)
- Health history and device data
- Only the last 2 conversation messages (for speed)

No FDA data, no guidelines, no personal library.

#### Expected Output

Plain text, no markdown formatting. Maximum 2-3 sentences under 50 words. Must end with "I'm running a deeper analysis now -- just a moment."

#### Key Design Decisions

- **Uses Haiku, not the configured tier** -- this is the only prompt that ignores the AiTier system entirely. Speed is the sole priority; clinical depth comes from the full response.
- **No source blocks** -- the `[sources]` attribution is intentionally omitted to keep the response minimal.
- **Urgency short-circuit** -- if critical symptoms are mentioned, the quick response becomes "This sounds urgent. Please contact your doctor or call 911 immediately." without waiting for the full analysis.
- **Max 150 tokens** -- hard cap to ensure sub-second streaming.

---

### medical-explainer

**File:** `prompts/medical-explainer.md`
**Service:** `App\Services\AI\MedicalExplainer`
**Model:** Current tier model (Opus 4.6 or Sonnet)

#### Purpose

Powers the tap-to-explain feature. When a patient taps a highlighted medical term in their visit notes, this prompt generates a contextual explanation of that specific term in plain language, tied to their visit.

#### Input Context

Full visit context via `ContextAssembler::assembleForVisit($visit, 'medical-explainer')`, same 8-layer assembly as the Q&A assistant. The user message specifies the element to explain and optionally which section it comes from.

#### Expected Output

Streaming text response with 3-5 short paragraphs:
1. **Simple definition** -- 1-2 sentences, plain language
2. **In your visit context** -- how this relates to what happened in the visit
3. **What this means for you** -- practical implications
4. **Related guideline context** -- supporting clinical guidelines (if relevant)

#### Key Design Decisions

- **Always visit-contextualized** -- the prompt requires connecting every explanation to the patient's specific visit, not generic medical dictionary definitions. "PVCs" is explained in the context of their EKG results, not as an abstract concept.
- **Analogy-friendly** -- the prompt encourages analogies ("Think of PVCs like your heart skipping a beat, like a hiccup") to make medical concepts accessible.
- **Never contradicts the doctor** -- explanations contextualize the doctor's findings but never second-guess clinical decisions.
- **No new information** -- the AI is restricted to explaining what already exists in the visit context, never introducing new diagnoses or recommendations.

#### Max Tokens

2,048

---

### patient-education

**File:** `prompts/patient-education.md`
**Service:** `App\Services\AI\PatientEducationGenerator`
**Model:** Current tier model (Opus 4.6 or Sonnet)

#### Purpose

Generates a comprehensive, personalized health guide document after a visit. This is a multi-page document covering conditions, medications, diet, warning signs, and follow-up questions -- designed to replace generic patient handouts with visit-specific content.

#### Input Context

Full visit context via `ContextAssembler::assembleForVisit($visit, 'patient-education')`, plus verified data from medical database tools gathered in Phase 1.

**Two-phase architecture:**
1. **Phase 1 (Tool Use)** -- the AI calls medical database tools (`check_drug_interaction`, `get_drug_safety_info`, `get_lab_reference_range`) to gather verified data before writing. This is non-streaming.
2. **Phase 2 (Generation)** -- the AI writes the full education document with the gathered tool data injected as additional context. This is streaming.

#### Expected Output

Long-form markdown document with sections:
- Your Visit Summary
- Your Conditions Explained
- Your Medications Guide (with verified FDA data)
- Diet & Lifestyle Recommendations
- Warning Signs to Watch For
- Questions for Your Next Visit
- Glossary

#### Key Design Decisions

- **Tool use for accuracy** -- this is the only prompt that uses Opus 4.6 tool use. The AI autonomously decides which drugs to look up, which interactions to check, and which lab values to reference. This demonstrates Opus 4.6's agentic capabilities.
- **Verified data integration** -- tool results are formatted as a "VERIFIED MEDICAL DATABASE RESULTS" context block, so the AI can distinguish verified FDA data from visit-note data.
- **High max tokens (65,536)** -- the education document is intentionally comprehensive. The prompt explicitly says "use the full output capacity available."
- **Extended thinking** -- when thinking is enabled, the AI reasons through the clinical picture before writing, producing more coherent and medically accurate documents.

#### Max Tokens

65,536 (with thinking) or 16,000 (without thinking).

---

### visit-summarizer

**File:** `prompts/visit-summarizer.md`
**Service:** Not yet wired to a dedicated service (planned for summary generation)
**Model:** Current tier model

#### Purpose

Creates a warm, patient-friendly visit summary in structured markdown format. Designed to be the first thing a patient sees when they open the app after a visit -- a clear, actionable overview of what happened and what to do next.

#### Input Context

Expects structured visit data (from Visit Structurer), patient conditions/medications, and practitioner information.

#### Expected Output

Markdown-formatted summary with sections:
- Why You Visited
- What the Doctor Found
- Your Diagnosis (with medical term in parentheses)
- Your Medications (with simple instructions)
- What to Watch For
- Your Next Steps (as a checklist)
- Questions? (invitation to use the chat)

#### Key Design Decisions

- **Most important information first** -- the summary is structured so diagnosis and medication changes appear before lifestyle advice and follow-up details.
- **Medication changes highlighted** -- new, changed, and discontinued medications are clearly distinguished because medication confusion is the most common post-visit problem.
- **Checklist format for next steps** -- action items use `- [ ]` checkbox format to encourage patient engagement.
- **Warm but not patronizing tone** -- the prompt specifies "empathetic but not patronizing, informative but not overwhelming."
- **Always English** -- summary is generated in English regardless of transcript language.

---

## Clinical Processing Prompts

### scribe-processor

**File:** `prompts/scribe-processor.md`
**Service:** `App\Services\AI\ScribeProcessor`
**Model:** Current tier model (Opus 4.6 or Sonnet)

#### Purpose

Transforms a raw audio transcript of a doctor-patient visit into clean, structured clinical data. This is the first AI step in the pipeline -- raw speech-to-text output goes in, structured SOAP notes and extracted medical entities come out.

#### Input Context

Provided directly by the `ScribeProcessor` service:
1. Raw transcript text (from Whisper STT, may contain artifacts)
2. Visit metadata (specialty, date, practitioner name)

No `ContextAssembler` -- this prompt works with raw transcript only.

#### Expected Output

JSON object containing:
- `clean_transcript` -- diarized text with "Doctor:" / "Patient:" labels on every line
- `speakers` -- identified doctor and patient names
- `extracted_entities` -- symptoms, diagnoses, medications (with dose/frequency/route/status), tests, vitals, allergies, procedures
- `soap_note` -- 7 sections: chief_complaint, history_of_present_illness, review_of_systems, physical_exam, assessment, plan, current_medications
- `unclear_sections` -- flagged ambiguous segments

#### Key Design Decisions

- **Speaker diarization is critical** -- the prompt emphasizes that every line of dialogue must be labeled Doctor or Patient. Without reliable diarization, downstream prompts cannot distinguish clinical findings from patient complaints.
- **SOAP section separation is enforced** -- medications must ONLY appear in `plan` and `current_medications`, never in review_of_systems or physical_exam. This prevents data leakage between sections, which caused incorrect term extraction in early versions.
- **Language translation** -- the raw transcript may be in any language, but all structured output (entities, SOAP note) must be in English medical terminology. The clean transcript preserves the original language.
- **STT error correction** -- the prompt instructs correction of common speech-to-text errors (e.g., "propanol" to "propranolol") because medical term accuracy is critical for downstream term extraction and drug lookup.
- **Formatting rules** -- SOAP sections must use line breaks, bullet points, and numbered lists for patient readability. The prompt includes explicit formatting examples.
- **Extended thinking** -- when enabled (Better/Opus 4.6 tiers), the AI reasons through clinical entity extraction before producing structured output. Thinking budget: 6,000 tokens (Better) or 10,000 tokens (Opus 4.6).

#### Max Tokens

16,000 (with thinking) or 8,192 (without thinking).

---

### visit-structurer

**File:** `prompts/visit-structurer.md`
**Service:** Not yet wired to a dedicated service (planned for visit structuring)
**Model:** Current tier model

#### Purpose

Takes processed transcript data, discharge notes, and uploaded documents and organizes them into clearly defined visit sections that the patient can browse in the app UI. This is the bridge between raw clinical data and the structured visit view.

#### Input Context

Expects output from the Scribe Processor (SOAP note, entities), discharge notes, uploaded documents (lab results, imaging), and visit metadata (specialty, date, practitioner).

#### Expected Output

JSON object with:
- `visit_type` -- specialty classification
- `sections` -- 12 structured sections: reason_for_visit, symptoms, history, comorbidities, current_medications, physical_examination, tests, conclusions, recommendations, prescriptions, next_steps, additional_documents
- `specialty_data` -- specialty-specific fields (e.g., EKG interpretation, ECHO findings for cardiology)
- `completeness` -- score (0-1), missing sections, notes

#### Key Design Decisions

- **Strict section separation** -- each piece of data belongs in exactly one section. Symptoms never contain medications, physical_examination never contains test results. This rule exists because early versions had data bleeding across sections, confusing patients.
- **Source tracking** -- every section includes a `source` field (transcript, discharge, or both) for transparency about where the data came from.
- **Never fabricate data** -- empty sections are returned with empty content, never padded with invented data. The completeness score reflects what is actually available.
- **Specialty extensions** -- cardiology visits include EKG, ECHO, Holter, and stress test fields in `specialty_data`. The system is designed to be extensible to other specialties.

---

### term-extractor

**File:** `prompts/term-extractor.md`
**Service:** `App\Services\AI\TermExtractor`
**Model:** Opus 4.6 (production), Sonnet (tests)

#### Purpose

Extracts clinically relevant medical terms from SOAP note sections and returns their exact character positions with patient-friendly definitions. Used to power the tap-to-explain highlighting feature (PRD user story P3).

#### Input Format

The service concatenates non-empty SOAP sections from a `VisitNote` model into a single user message:

```
=== SECTION: chief_complaint ===
Patient reports heart palpitations occurring 3-4 times per week...

=== SECTION: history_of_present_illness ===
52-year-old male presenting with a 3-month history of intermittent heart palpitations...

=== SECTION: assessment ===
Premature ventricular contractions (PVCs) -- benign pattern...

=== SECTION: plan ===
1. Start propranolol 40 mg twice daily...
```

Sections processed: `chief_complaint`, `history_of_present_illness`, `review_of_systems`, `physical_exam`, `assessment`, `plan`, `follow_up`.

#### Expected Output

Pure JSON (no markdown fences) mapping section names to arrays of term objects:

```json
{
  "chief_complaint": [
    { "term": "palpitations", "start": 6, "end": 18, "definition": "A sensation of your heart beating rapidly or fluttering." }
  ],
  "assessment": [
    { "term": "Premature ventricular contractions", "start": 0, "end": 35, "definition": "Extra heartbeats originating in the lower chambers of the heart." },
    { "term": "PVCs", "start": 36, "end": 40, "definition": "Abbreviation for Premature Ventricular Contractions." }
  ]
}
```

Each entry:
- `term` (string) -- exact text as it appears in the section
- `start` (int) -- 0-based character index of the first character
- `end` (int) -- 0-based index one past the last character (end = start + length)
- `definition` (string) -- 1-2 sentence patient-friendly explanation at 8th grade reading level

#### Extraction Rules

1. **Include:** diagnoses, symptoms, procedures, medications (brand AND generic), lab values, anatomical terms, medical abbreviations, imaging studies, vital sign terms, dosage forms, frequency terms, medical devices
2. **Exclude:** common clinical words ("patient", "history", "normal"), section headers, numbers without clinical context
3. **Specificity:** prefer longer phrases over individual words (e.g., "premature ventricular contractions" over "contractions")
4. **No overlaps:** if a longer phrase contains a shorter term, only the longer phrase is extracted
5. **Abbreviations:** include as separate terms only when they appear independently, not when inside parentheses following the full term
6. **Cross-section extraction:** if a term appears in multiple sections, extract it in each section with correct per-section offsets
7. **Ordering:** terms listed in order of appearance (ascending start position)
8. **Thoroughness:** 8-20 terms per section is typical. When in doubt, include the term.

#### Definition Rules

- **Never imply causal relationships** between medications and outcomes (e.g., never say "your condition improved because of X"). Use phrasing like "your doctor noted improvement."
- Definitions relate to the patient's specific visit when possible
- 8th grade reading level, no jargon

#### Validation

The `TermExtractor` service validates every returned offset server-side:
1. Checks `start >= 0`, `end <= strlen(text)`, and `end > start`
2. Extracts `substr(text, start, end - start)` and compares case-insensitively against the claimed term
3. Invalid entries are dropped and logged to the `ai` channel
4. The frontend (`HighlightedText.vue`) performs a second validation pass and falls back to string search if offsets don't match

#### Version History

| Date | Change | Reason |
|------|--------|--------|
| 2026-02-11 | v1 -- Initial prompt | Medical term highlighting feature |
| 2026-02-13 | v2 -- Added `definition` field, expanded extraction scope, added causation guardrail | Patient education depth; avoid implying drug-outcome causation |

#### Max Tokens

4,096 -- sufficient for ~40 terms across 7 SOAP sections.

---

### context-guidelines

**File:** `prompts/context-guidelines.md`
**Service:** `App\Services\AI\ContextAssembler` (reference template, not a direct AI prompt)
**Model:** N/A -- this is a formatting standard, not an AI instruction

#### Purpose

Defines how clinical guidelines (ESC, AHA, etc.) are formatted and injected into the AI context window. This is not a prompt for a specific AI subsystem but a formatting template that ensures consistent structure across different guideline sources.

#### How It Works

Clinical guidelines are loaded once per chat session as static context inside the system prompt (for prompt caching). Only guidelines relevant to the visit's specialty and diagnoses are included. The `GuidelinesRepository` service selects and formats relevant guidelines using this template.

Key formatting structure:
- Source organization (ESC, AHA), title, year, DOI, PMID
- Key recommendations extracted for the patient's condition
- Evidence level classification (Class of recommendation, Level of evidence)
- Patient-relevant sections specifically curated for explanation use

#### Key Design Decisions

- **Citation requirements** -- every medical reference must include a PMID or DOI. References without verifiable identifiers are excluded from patient-facing responses.
- **Opus 4.6 tier only** -- guidelines are loaded only when `AiTier::guidelinesEnabled()` returns true, which is Opus 4.6 only. Lower tiers skip guidelines to save context space and cost.
- **Pre-processed, not raw** -- the template notes that guidelines should be pre-processed to extract relevant sections rather than loading entire 200-page documents. The 1M token context window can accommodate 4-8 full guideline documents.
- **Available demo guidelines** -- for the cardiology demo, 5 guidelines are pre-loaded: ESC Ventricular Arrhythmias 2022, ESC Heart Failure 2023, AHA Heart Failure 2022, ESC Dyslipidaemia 2019, ESC CVD Prevention 2021.

---

## Safety Prompts

### escalation-detector

**File:** `prompts/escalation-detector.md`
**Service:** `App\Services\AI\EscalationDetector`
**Model:** Current tier model (Opus 4.6 with thinking for nuanced evaluation, Sonnet for standard)

#### Purpose

Analyzes patient messages for signs of urgent or dangerous medical situations. This is the safety net that determines whether a patient message requires emergency intervention, urgent doctor contact, or can be handled by the normal chat flow.

#### Input Context

Provided directly by the `EscalationDetector` service:
1. Patient's message text
2. Patient's known conditions and medications (from visit)
3. Visit specialty context

No full `ContextAssembler` -- the evaluator needs minimal context for speed.

#### Expected Output

JSON object:
```json
{
  "is_urgent": true,
  "severity": "critical|high|moderate|low",
  "reason": "Brief explanation of why this was flagged",
  "trigger_phrases": ["chest pain", "can't breathe"],
  "recommended_action": "Call 911 immediately|Contact your doctor today|Discuss at next visit|No action needed",
  "context_factors": ["Patient has cardiac history, increasing urgency"]
}
```

#### Urgency Levels

- **CRITICAL** -- immediate escalation: chest pain, difficulty breathing, stroke signs, loss of consciousness, suicidal ideation, severe bleeding, sudden vision loss
- **HIGH** -- urgent, contact doctor today: worsening symptoms, medication side effects affecting daily function, fever above 38.5C, persistent vomiting
- **MODERATE** -- discuss at next visit: mild side effects, medication timing questions, non-urgent symptom changes
- **LOW** -- informational, no escalation: general questions, medication info requests, lifestyle questions

#### Key Design Decisions

- **Two-tier architecture: keyword-first, AI-second** -- the current implementation uses a fast keyword check against 20+ critical phrases as the primary detection. This runs in <1ms. The AI evaluation (`aiEvaluate()`) is preserved in code but currently skipped because it added 5-15 seconds of latency before the first chat token could stream. The keyword check catches explicit critical symptoms reliably.
- **Err on the side of caution** -- the prompt instructs: "when in doubt, escalate." A false positive (unnecessary emergency warning) is far less harmful than a false negative (missing a real emergency).
- **Condition-aware evaluation** -- when AI evaluation is used, the patient's known conditions modify severity assessment. Chest pain in a cardiac patient is higher urgency than in a patient with no cardiac history.
- **Never provides treatment advice** -- when severity is CRITICAL, the system interrupts normal chat and displays an emergency directive. No discussion of symptoms, no AI analysis -- just "Call 911."
- **Opus 4.6 extended thinking on escalation** -- only Opus 4.6 tier enables thinking for escalation decisions (`escalationThinkingEnabled()`), giving the AI 6,000 thinking tokens to reason through ambiguous cases.

#### Max Tokens

512 (standard path) or 8,000 (with thinking on Opus 4.6).

---

## Doctor Tool Prompts

### doctor-inquiry

**File:** `prompts/doctor-inquiry.md`
**Service:** `App\Http\Controllers\Api\DoctorController::inquire()` (inline usage)
**Model:** Configured model (defaults to `claude-opus-4-6`)

#### Purpose

Helps physicians analyze patient messages sent through the app's notification system. When a patient sends a concern or question to their doctor, this prompt provides AI-powered clinical decision support -- summarizing the patient's concern in clinical terms, assessing clinical significance, drafting a response, and suggesting follow-up actions.

#### Input Context

Full visit context via `ContextAssembler::assembleForVisit($visit, 'doctor-inquiry')`, plus the patient's notification message (category and body text).

#### Expected Output

Streaming text response with 5 sections:
1. **Patient's Concern** -- restated in clinical terms
2. **Clinical Relevance** -- is this expected? Is it a complication or adverse reaction? Red flags?
3. **Relevant Context** -- specific elements from visit record, medications, observations
4. **Suggested Response** -- draft reply in plain patient-friendly language
5. **Recommended Actions** -- 0-3 specific clinical actions (follow-up, med adjustments, tests, referrals)

#### Key Design Decisions

- **For the doctor, not the patient** -- this is the only prompt that generates physician-facing output. Language is clinical, not simplified. The suggested patient response section IS in plain language, but the analysis sections use medical terminology.
- **Respects clinical autonomy** -- presents options, not directives. "Consider" and "might suggest" rather than "you should."
- **Always flags urgency first** -- if the patient's message suggests an emergency, this is stated at the top of the response before any analysis.
- **Medication context awareness** -- specifically checks for potential side effects, interactions, and timing issues relevant to the patient's current prescriptions.
- **References actual values** -- the prompt requires citing specific dates, values, and medication names from the patient record rather than making generic statements.

#### Max Tokens

4,096

---

### meds-analyzer

**File:** `prompts/meds-analyzer.md`
**Service:** Not yet wired to a dedicated service (data structure defined for medication review feature)
**Model:** Current tier model

#### Purpose

Analyzes a patient's full medication regimen in the context of their visit. Provides clear information about each drug, flags potential drug-drug interactions with severity levels, identifies new/changed/continued/discontinued medications, and gives practical guidance on timing and side effects.

#### Input Context

Expects:
1. List of medications with dosing information
2. Patient's conditions and diagnoses
3. Visit context (why each medication was prescribed)
4. RxNorm drug data (interactions, contraindications)

#### Expected Output

JSON object with:
- `medications[]` -- for each drug: name, generic, dose, frequency, route, purpose, status (new/changed/continued/discontinued), instructions, side effects (with severity and action), warnings
- `interactions[]` -- drug pairs with severity (mild/moderate/severe), description, recommendation
- `changes_summary` -- categorized lists of new, changed, continued, and discontinued medications

#### Key Design Decisions

- **Never suggests medication changes** -- the prompt explicitly forbids recommending new prescriptions or dosage modifications. It explains what the doctor prescribed, not what should be prescribed.
- **Never contradicts the prescribing doctor** -- even if an interaction is found, the prompt notes it factually without undermining the doctor's decision.
- **Severity-stratified interactions** -- interactions are classified as mild/moderate/severe with specific recommendations, not just listed as present/absent.
- **Pharmacist referral** -- for detailed interaction questions, the prompt always recommends pharmacist consultation.
- **Status tracking** -- every medication is classified as new, changed, continued, or discontinued, because understanding what changed is the most common patient need after a visit.

---

## Document Analysis Prompts

### document-analyzer

**File:** `prompts/document-analyzer.md`
**Service:** `App\Services\AI\DocumentAnalyzer`
**Model:** Default configured model

#### Purpose

Analyzes clinical documents uploaded to a specific visit -- ECG readings, imaging studies, lab results, discharge summaries. Uses Claude's vision capabilities to process both images and PDFs, returning structured findings that help patients understand their test results.

#### Input Context

Provided directly by the `DocumentAnalyzer` service:
1. Document file as a visual attachment (base64-encoded image or PDF)
2. Brief visit context: date, specialty, assessment summary

No `ContextAssembler` -- this prompt works with the document and minimal visit context.

#### Expected Output

JSON object with:
- `summary` -- 1-2 sentence plain-language description
- `findings[]` -- observed findings with location and significance (normal/mild/moderate/significant/critical)
- `key_values[]` -- extracted measurements with reference ranges and status (normal/low/high/abnormal)
- `confidence` -- high/medium/low based on document clarity
- `document_category` -- ecg/imaging/lab_result/discharge_summary/prescription/other
- `safety_note` -- mandatory disclaimer (always included)

#### Key Design Decisions

- **Descriptive, not diagnostic** -- uses language like "appears to show", "consistent with", "suggestive of" rather than definitive diagnostic claims. The AI describes observations, not diagnoses.
- **Mandatory safety note** -- the `safety_note` field is required in every response. It cannot be omitted regardless of confidence level.
- **Critical findings prominently flagged** -- if a critical finding is detected, the summary is prefixed with "IMPORTANT:" to ensure visibility.
- **Confidence scoring** -- image quality directly affects confidence. Poor quality documents get "low" confidence with an explanation of the limitation rather than speculative findings.
- **Multimodal input** -- supports JPEG, PNG, GIF, WebP images and PDF documents via Claude's vision API. PDFs are sent as `document` type, images as `image` type.

#### Max Tokens

4,096

---

### library-item-analyzer

**File:** `prompts/library-item-analyzer.md`
**Service:** `App\Services\AI\LibraryItemAnalyzer`
**Model:** Current tier model (with extended thinking)

#### Purpose

Analyzes documents that patients upload to their personal medical library -- clinical guidelines, research papers, patient education materials, drug information sheets. Unlike `document-analyzer` (which processes visit-specific clinical documents), this prompt processes reference material the patient collects for personal study.

#### Input Context

Document text extracted by the `LibraryItemAnalyzer` service (via `pdftotext` CLI for PDFs, or Claude-based HTML extraction for web articles). The analysis runs through a 5-step pipeline:

1. **Extract Text** -- get raw text from PDF or URL
2. **Analyze** -- this prompt: extract title, summary, key findings, recommendations, publication info
3. **Categorize** -- classify by medical topics, evidence level, specialty, document type, ICD-10 codes
4. **Relate to Patient** -- assess relevance to the patient's conditions and medications
5. **Verify** -- validate the combined analysis for accuracy, safety, and copyright compliance

Steps 2-5 each use separate AI calls with extended thinking.

#### Expected Output

JSON object with:
- `title` -- document title
- `summary` -- 2-3 paragraph patient-friendly summary
- `key_findings[]` -- extracted key findings
- `recommendations[]` -- actionable recommendations
- `publication_info` -- authors, journal, year, DOI

#### Key Design Decisions

- **Multi-step pipeline** -- rather than a single large prompt, the analysis is broken into 5 focused steps. Each step uses extended thinking and has a specific, verifiable output. This architecture allows the verification step (step 5) to catch errors from earlier steps.
- **Patient-friendly summaries** -- even for technical research papers, the summary is written at 8th grade reading level. The prompt forbids reproducing large chunks of copyrighted text -- it must paraphrase and summarize.
- **Evidence level honesty** -- the categorization step classifies evidence as A/B/C and explicitly says "don't inflate." A patient blog post should not be categorized as Level A evidence.
- **Patient relevance scoring** -- the "relate to patient" step produces a 0-1 relevance score and matches the document against the patient's conditions and medications. This enables the Q&A assistant to prioritize relevant library items in context.
- **Copyright compliance** -- the verification step checks for verbatim copyrighted content in summaries.
- **TDM opt-out respect** -- when fetching web URLs, the service checks for `TDM-Reservation: 1` headers and marks content accordingly under EU TDM exception.

#### Max Tokens

Variable per step: 8,000-14,000 per step (thinking budget + output), with budget_tokens scaling by tier (6,000-10,000 for Opus 4.6).

---

## Architecture Notes

### Prompt Loading

All prompts are loaded via `App\Services\AI\PromptLoader`, which reads `.md` files from the `prompts/` directory by name:

```php
$loader->load('qa-assistant');      // loads prompts/qa-assistant.md
$loader->load('term-extractor');    // loads prompts/term-extractor.md
```

### AI Tier System

PostVisit uses three AI tiers that affect model selection, thinking budgets, and context depth:

| Tier | Model | Thinking | Guidelines | Caching |
|------|-------|----------|------------|---------|
| **Good** | Claude Sonnet 4.5 | Off | No | No |
| **Better** | Claude Opus 4.6 | On (moderate) | No | Yes |
| **Opus 4.6** | Claude Opus 4.6 | On (full) | Yes | Yes |

The tier is set globally via the `AiTierManager` and affects all prompts except `qa-assistant-quick` (always Haiku) and `document-analyzer` (always default model).

### Prompt Caching

On Better and Opus 4.6 tiers, the system prompt and clinical guidelines are wrapped in `TextBlockParam` with `CacheControlEphemeral` for Anthropic's prompt caching. This means the first request in a session incurs full processing cost, but subsequent requests reuse the cached system prompt and guidelines, reducing latency and cost by ~90% for the cached portion.

### Extended Thinking Budgets

Thinking token budgets are allocated per subsystem and tier:

| Subsystem | Good | Better | Opus 4.6 |
|-----------|------|--------|----------|
| chat | 0 | 4,000 | 8,000 |
| scribe | 0 | 6,000 | 10,000 |
| escalation | 0 | 0 | 6,000 |
| reasoning | 0 | 6,000 | 10,000 |
| library | 0 | 6,000 | 10,000 |

Additionally, the Q&A assistant uses **adaptive thinking** based on question complexity (effort classification):

| Effort | Opus 4.6 Thinking Budget | Opus 4.6 Max Tokens |
|--------|--------------------------|---------------------|
| low | 1,024 | 4,096 |
| medium | 4,000 | 8,000 |
| high | 8,000 | 16,000 |
| max | 16,000 | 32,000 |
