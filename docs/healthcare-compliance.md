# Healthcare Compliance — PostVisit.ai

> Last updated: 2026-02-15

PostVisit.ai is a healthcare application that handles clinical visit data, patient records, and AI-assisted medical explanations. This document describes how the platform addresses healthcare regulatory requirements across US and EU frameworks.

> **Note:** PostVisit.ai is a hackathon prototype. No real patient data is processed. All clinical scenarios are fictional. This document describes the compliance architecture implemented in the codebase.

---

## HIPAA (US)

The Health Insurance Portability and Accountability Act governs the handling of Protected Health Information (PHI) in US healthcare. PostVisit.ai implements HIPAA's core safeguards:

### PHI Classification

All PHI fields are explicitly identified in the data model:

| PHI Category | Data Elements | Storage |
|-------------|--------------|---------|
| **Visit recordings** | Audio files, transcripts, consent timestamps | `transcripts` table |
| **Clinical notes** | SOAP notes, discharge summaries, observations | `visit_notes`, `observations` |
| **Diagnoses** | ICD-10 codes, condition descriptions, onset dates | `conditions` |
| **Prescriptions** | Medications, dosages, frequencies, instructions | `prescriptions` |
| **Lab results** | Vitals, lab values, reference ranges | `observations` |
| **Demographics** | Name, DOB, gender, contact info | `patients` |
| **Patient questions** | Chat messages, AI responses | `chat_messages` |

### Access Control (45 CFR 164.312(a))

Role-based access control with ownership verification:

- **Patients** see only their own data. `PatientPolicy` enforces `$user->patient_id === $patient->id`.
- **Doctors** see only their patients (those with visits). `VisitPolicy` enforces `$user->practitioner_id === $visit->practitioner_id`.
- **Admin** has full access for system operations.
- **RoleMiddleware** protects doctor/admin routes at the HTTP layer.
- **UUID primary keys** on all 22 tables prevent enumeration attacks (non-sequential, non-guessable).

### Audit Trail (45 CFR 164.312(b))

`AuditMiddleware` logs every authenticated access to PHI endpoints:

| Field | Description |
|-------|-------------|
| `user_id` | Authenticated user |
| `user_role` | patient, doctor, admin |
| `action_type` | read, create, update, delete, download |
| `resource_type` | visit, patient, transcript, observation, medication, etc. |
| `resource_id` | UUID of accessed resource |
| `phi_accessed` | Boolean flag |
| `phi_elements` | Array of PHI categories accessed (e.g., `['visit_data', 'clinical_notes']`) |
| `ip_address` | Client IP |
| `session_id` | Session identifier |
| `accessed_at` | UTC timestamp |

Audit logs are append-only and accessible via `GET /api/v1/audit/logs` (doctor/admin only) with CSV export capability.

### Minimum Necessary (45 CFR 164.502(b))

The AI pipeline implements data minimization:

- `ContextAssembler` sends only clinically relevant data to the AI model
- Direct patient identifiers (name, DOB, insurance ID) are stripped from AI prompts when not clinically necessary
- AI context is scoped to a single visit — no cross-patient data mixing
- Each AI request includes only the layers needed for the specific question

### Encryption

| Layer | Method |
|-------|--------|
| In transit | TLS 1.2+ (Let's Encrypt) on all endpoints |
| At rest (database) | PostgreSQL encrypted volumes |
| At rest (application) | Laravel `encrypted` casts available for PHI columns |
| Secrets | All API keys and credentials in `.env`, never committed |
| Passwords | bcrypt with `BCRYPT_ROUNDS=12` |

### BAA Readiness

The architecture supports a Business Associate Agreement with Anthropic:
- Anthropic's API does not use customer data for model training (per Anthropic policy)
- Data minimization ensures only necessary clinical context reaches the API
- No PHI is stored on Anthropic's infrastructure beyond request processing

### Patient Rights

- **Right to Access**: Patients can view all their own data via API — visits, notes, prescriptions, observations, chat history
- **Health Record endpoint**: `GET /api/v1/patients/{id}/health-record` returns the full patient record
- **Breach scope assessment**: Audit logs enable identification of exactly which PHI was accessed, by whom, and when

---

## GDPR (EU)

The General Data Protection Regulation governs personal data processing in the European Union. PostVisit.ai implements GDPR's core principles:

### Lawful Basis (Article 6)

Patient consent is the lawful basis for processing:

- **Patient model**: `consent_given`, `consent_date`, `data_sharing_consent` fields
- **Transcript model**: `patient_consent_given`, `consent_timestamp` — consent recorded before any recording
- **Consent model**: Dedicated `consents` table tracking consent type (privacy, data sharing, research, telehealth, recording), version, and timestamp
- **Validation**: API requires `patient_consent_given: true` before accepting transcript uploads

### Purpose Limitation (Article 5(1)(b))

- AI context is scoped to a single visit — the system never mixes data across patients
- Each AI conversation is bound to a specific visit and patient
- Clinical guidelines and drug data are public domain, not patient-specific

### Data Minimization (Article 5(1)(c))

- Only clinically necessary data is sent to the AI model
- Patient identifiers are excluded from AI prompts when not clinically required
- `ContextAssembler` builds context in layers, including only what's needed for the specific question

### Right of Access (Article 15)

- Full patient data accessible via API: visits, notes, prescriptions, observations, chat history
- `GET /api/v1/patients/{id}/health-record` provides a comprehensive health record view
- Audit logs show every access to patient data

### Right to Erasure (Article 17)

- Data model supports deletion with cascading relationships configured
- Patient record deletion cascades to all dependent data (visits, notes, prescriptions, observations)

### Data Portability (Article 20)

The data model is aligned with FHIR R4 (Fast Healthcare Interoperability Resources):

| FHIR Resource | PostVisit Model | Key Fields |
|--------------|----------------|------------|
| Patient | `Patient` | demographics, consent, identifiers |
| Encounter | `Visit` | dates, type, status, practitioner |
| Observation | `Observation` | vitals, lab results, values, units |
| Condition | `Condition` | ICD-10 codes, onset, status |
| MedicationRequest | `Prescription` | medication, dosage, frequency |
| Practitioner | `Practitioner` | name, specialty, NPI |
| DocumentReference | `Document` | uploaded clinical documents |

FHIR alignment enables standard health data export and interoperability with EHR systems.

### Privacy by Design (Article 25)

Built into the architecture from day one:
- UUID primary keys (non-guessable) on all tables
- Role-based access control with ownership verification
- Audit logging on every PHI access
- Cookie-based SPA auth (Sanctum) — no tokens in localStorage
- CSRF protection on all state-changing requests

### DPO Contact

`SECURITY.md` provides a security contact for privacy inquiries and responsible disclosure.

---

## AI-Specific Safeguards

Healthcare AI introduces unique compliance considerations:

### Clinical Safety

- **No diagnosis**: The AI explains what the doctor said — it never issues new diagnoses
- **No prescription**: The AI explains prescribed medications — it never suggests new ones or dosage changes
- **No contradiction**: The AI contextualizes recommendations with guidelines — it never overrides clinical decisions
- **Escalation detection**: `EscalationDetector` monitors for urgent symptoms (chest pain, breathing difficulty, suicidal ideation) and redirects to emergency services

### Prompt Injection Protection

The QA assistant system prompt includes explicit injection protection:
- Ignores instructions embedded in user messages, transcripts, or chat history
- Rejects attempts to override role, safety guardrails, or response format
- Refuses non-health topics and redirects to health-related questions

### AI Transparency

- Streaming responses show AI reasoning in real time (extended thinking)
- Source attribution on every response — patients see which data sources the AI used
- Doctor dashboard provides full chat audit trail for review

### Rate Limiting

3-layer protection against API abuse:
1. Route-level throttle: 10 requests/min, 30 requests/hr
2. Global daily budget: 500 AI requests/day (`AiBudgetMiddleware`)
3. Per-user limit: 50 AI requests/day

---

## Additional Standards Alignment

| Standard | Relevance | Status |
|----------|-----------|--------|
| **FHIR R4** | Data model aligned with FHIR resources | Implemented |
| **SOC 2 Type II** | Audit logging, access controls, encryption provide foundation | Architecture ready |
| **HITRUST CSF** | Healthcare-specific controls mapped to implementation | Architecture ready |
| **ISO 27001** | Information security management framework alignment | Architecture ready |

---

## Implementation Evidence

All controls described in this document are implemented in the codebase:

| Control | Implementation File |
|---------|-------------------|
| Access policies | `app/Policies/VisitPolicy.php`, `app/Policies/PatientPolicy.php` |
| Role middleware | `app/Http/Middleware/RoleMiddleware.php` |
| Audit logging | `app/Http/Middleware/AuditMiddleware.php`, `app/Models/AuditLog.php` |
| Data minimization | `app/Services/AI/ContextAssembler.php` |
| Consent tracking | `app/Models/Consent.php`, `app/Models/Patient.php`, `app/Models/Transcript.php` |
| Escalation detection | `app/Services/AI/EscalationDetector.php` |
| Prompt injection protection | `prompts/qa-assistant.md` |
| Rate limiting | `app/Http/Middleware/AiBudgetMiddleware.php` |
| XSS prevention | `resources/js/utils/sanitize.ts` (DOMPurify) |
| FHIR data model | `database/migrations/` (22 tables, UUID primary keys) |
| Audit API | `app/Http/Controllers/Api/AuditController.php` |

---

*For OWASP Top 10 security audit, see [`docs/security-audit.md`](security-audit.md).*
*For security policies and responsible disclosure, see [`SECURITY.md`](../SECURITY.md).*
