# Data Model — PostVisit.ai

> Research + design from 2026-02-10. FHIR R4 aligned, HIPAA/GDPR-aware.
> Database TBD: PostgreSQL (recommended — jsonb, tsvector) or MySQL.

## Guiding Principle

Every entity maps to an FHIR R4 resource. Column naming and statuses are FHIR-compliant. This makes the system interoperability-ready with EHR from day zero.

## Entity Map → FHIR

| Table | FHIR Resource | Purpose |
|--------|---------------|-----|
| `patients` | Patient | Demographics, contact, consent |
| `practitioners` | Practitioner | Physician: details, specialty, NPI |
| `organizations` | Organization | Facility/clinic |
| `visits` | Encounter | Visit — central aggregate |
| `observations` | Observation | Lab results, vitals, specialty findings |
| `conditions` | Condition | Diagnoses (ICD-10, SNOMED) |
| `medications` | Medication | Drug master data |
| `prescriptions` | MedicationRequest | Prescriptions with dosage |
| `medication_interactions` | DetectedIssue | Drug-drug interactions |
| `documents` | DocumentReference | Files: lab reports, imaging, notes |
| `diagnostic_reports` | DiagnosticReport | Laboratory/imaging reports |
| `visit_notes` | Composition | Clinical note (SOAP) |
| `transcripts` | Media / DocumentReference | Conversation transcript (ambient scribing) |
| `chat_sessions` | Communication | Patient Q&A after visit |
| `chat_messages` | — (child of Communication) | Individual chat messages |
| `audit_logs` | AuditEvent | HIPAA audit trail |
| `consents` | Consent | GDPR consent tracking |
| `users` | — | Auth, roles (Laravel Sanctum) |

## Coding Systems

| Domain | Standard | Example code | Where used |
|--------|----------|--------------|---------------|
| Diagnoses | ICD-10-CM / ICD-11 | I10 (hypertension) | `conditions.code` |
| Clinical | SNOMED CT | 38341003 (hypertension) | `conditions.code`, `observations` |
| Lab tests | LOINC | 2345-7 (glucose serum) | `observations.code` |
| Vitals | LOINC | 8480-6 (systolic BP) | `observations.code` |
| Medications | RxNorm | 1110711 (metformin) | `medications.rxnorm_code` |
| Medications (global) | ATC | A10BA02 | `medications.atc_code` |
| Procedures | SNOMED / CPT | 29303005 (EKG) | `observations.code` |
| Specialties | SNOMED | 394577000 (cardiology) | `practitioners.primary_specialty` |

All coding systems listed above are **open source / free** (see licenses.md).

## Entities — Definitions

### patients

```
id                          uuid PK
fhir_patient_id             varchar unique
first_name                  varchar
last_name                   varchar
dob                         date
gender                      enum (male|female|other|unknown)
email                       varchar unique indexed
phone                       varchar
preferred_language          varchar default 'en' (ISO 639-1)
timezone                    varchar default 'UTC'
mrn                         varchar indexed (Medical Record Number)
ssn_encrypted               varchar nullable (AES-256, HIPAA)

# GDPR
consent_given               boolean
consent_date                timestamp nullable
data_sharing_consent        boolean
right_to_erasure_requested  boolean

# Audit
created_by                  uuid FK(users)
created_at                  timestamp
updated_at                  timestamp
deleted_at                  timestamp nullable (soft delete)
```

### practitioners

```
id                          uuid PK
fhir_practitioner_id        varchar unique
first_name                  varchar
last_name                   varchar
email                       varchar unique
npi                         varchar unique indexed
license_number              varchar
medical_degree              enum (MD|DO|PA|NP|...)
primary_specialty           varchar (SNOMED code)
secondary_specialties       jsonb (array of SNOMED codes)
organization_id             uuid FK(organizations)
created_at                  timestamp
updated_at                  timestamp
```

### visits (central aggregate)

```
id                          uuid PK
fhir_encounter_id           varchar unique
patient_id                  uuid FK(patients) indexed
practitioner_id             uuid FK(practitioners) indexed
organization_id             uuid FK(organizations)
visit_type                  enum (office_visit|telehealth|emergency|inpatient)
class                       enum (AMB|EMER|IMP|...) — FHIR class codes
visit_status                enum (planned|in_progress|completed|cancelled)
service_type                varchar (SNOMED code)
reason_for_visit            text (chief complaint)
reason_codes                jsonb (SNOMED codes)
summary                     text
started_at                  timestamp
ended_at                    timestamp
duration_minutes            integer
provider_notes_followup     text

# Audit
created_by                  uuid FK(users)
created_at                  timestamp
updated_at                  timestamp
```

### observations (lab results, vitals, specialty findings)

```
id                          uuid PK
fhir_observation_id         varchar unique
patient_id                  uuid FK(patients) indexed
visit_id                    uuid FK(visits) nullable indexed
practitioner_id             uuid FK(practitioners) nullable

# Coding
code_system                 enum (LOINC|SNOMED-CT|LOCAL)
code                        varchar indexed
code_display                varchar
category                    varchar (vital-signs|laboratory|imaging|exam)
status                      enum (registered|preliminary|final|amended|cancelled)

# Value (polymorphic)
value_type                  enum (quantity|string|boolean|codeable)
value_quantity              decimal nullable
value_unit                  varchar nullable
value_string                text nullable
value_boolean               boolean nullable

# Reference range
reference_range_low         decimal nullable
reference_range_high        decimal nullable
reference_range_text        text nullable
interpretation              enum (L|LL|H|HH|N) nullable

# Timing
effective_date              date
issued_at                   timestamp

# Specialty extension (KEY: extensibility across specialties)
specialty_data              jsonb nullable

# Audit
created_by                  uuid FK(users)
created_at                  timestamp
updated_at                  timestamp
```

**`specialty_data` — this is how extensibility is achieved:**

Cardiology (ECHO):
```json
{
  "specialty": "cardiology",
  "exam_type": "echocardiogram",
  "findings": {
    "ejection_fraction": 55,
    "ef_unit": "%",
    "wall_motion": "normal",
    "valvular_disease": false
  }
}
```

Cardiology (EKG):
```json
{
  "specialty": "cardiology",
  "exam_type": "12-lead EKG",
  "findings": {
    "heart_rate": 78,
    "pr_interval_ms": 160,
    "qrs_duration_ms": 100,
    "interpretation": "normal sinus rhythm",
    "pvc_detected": true
  }
}
```

Endocrinology:
```json
{
  "specialty": "endocrinology",
  "hba1c_current": 7.2,
  "hba1c_previous": [7.0, 6.8],
  "glucose_trend": "stable",
  "insulin_regimen": {
    "type": "basal-bolus",
    "basal_dose_units": 20
  }
}
```

### conditions (diagnoses)

```
id                          uuid PK
fhir_condition_id           varchar unique
patient_id                  uuid FK(patients) indexed
visit_id                    uuid FK(visits) nullable indexed
code_system                 enum (ICD-10-CM|SNOMED-CT|ICD-11)
code                        varchar indexed
code_display                varchar
category                    enum (problem-list-item|encounter-diagnosis|chief-complaint)
clinical_status             enum (active|inactive|resolved|remission)
verification_status         enum (unconfirmed|provisional|confirmed|refuted)
severity                    enum (mild|moderate|severe) nullable
onset_date                  date nullable
abatement_date              date nullable
clinical_notes              text nullable
created_by                  uuid FK(users)
created_at                  timestamp
updated_at                  timestamp
```

### medications (drug master data)

```
id                          uuid PK
rxnorm_code                 varchar unique indexed
atc_code                    varchar nullable
ndc_code                    varchar nullable
generic_name                varchar indexed
brand_names                 jsonb (array of strings)
display_name                varchar
form                        enum (tablet|capsule|injection|solution|...)
strength_value              decimal
strength_unit               varchar
ingredients                 jsonb nullable
black_box_warning           boolean default false
pregnancy_category          enum (A|B|C|D|X) nullable
source                      enum (rxnorm|drugbank|local)
source_last_updated         timestamp
is_active                   boolean default true
created_at                  timestamp
updated_at                  timestamp
```

### prescriptions (MedicationRequest)

```
id                          uuid PK
fhir_medication_request_id  varchar unique
patient_id                  uuid FK(patients) indexed
practitioner_id             uuid FK(practitioners) indexed
visit_id                    uuid FK(visits) nullable indexed
medication_id               uuid FK(medications) indexed
status                      enum (active|on-hold|completed|stopped|cancelled)
intent                      enum (order|plan|proposal)

# Dosage
dose_quantity               decimal
dose_unit                   varchar
frequency                   varchar (e.g. "twice daily")
frequency_text              varchar (e.g. "Every 12 hours with meals")
route                       enum (oral|IV|IM|topical|inhaled|...)

# Duration
start_date                  date
end_date                    date nullable
duration_days               integer nullable

# Refills
number_of_refills           integer default 0
refills_remaining           integer default 0

# Instructions
special_instructions        text nullable
indication                  varchar nullable
indication_code             varchar nullable (SNOMED)
substitution_allowed        boolean default true

# Audit
created_by                  uuid FK(users)
created_at                  timestamp
updated_at                  timestamp
```

### medication_interactions

```
id                          uuid PK
drug_a_id                   uuid FK(medications) indexed
drug_b_id                   uuid FK(medications) indexed
severity                    enum (minor|moderate|major|contraindicated)
description                 text
management                  text
source_database             enum (drugbank|rxnorm|fda|local)
should_alert                boolean default true
created_at                  timestamp

UNIQUE(drug_a_id, drug_b_id) — enforce A < B
```

### documents (DocumentReference)

```
id                          uuid PK
fhir_document_reference_id  varchar unique
patient_id                  uuid FK(patients) indexed
visit_id                    uuid FK(visits) nullable indexed
title                       varchar
description                 text nullable
document_type               enum (lab_report|imaging_report|discharge_summary|progress_note|referral)
content_type                enum (pdf|docx|txt|html|dicom)
file_path                   varchar (encrypted reference)
file_size                   bigint
file_hash                   varchar (SHA-256)
status                      enum (current|superseded|entered-in-error)
document_date               date
confidentiality_level       enum (U|L|M|H|R) — FHIR codes
created_by                  uuid FK(users)
created_at                  timestamp
updated_at                  timestamp
retention_until             date nullable
```

### transcripts (ambient scribing)

```
id                          uuid PK
visit_id                    uuid FK(visits) indexed
patient_id                  uuid FK(patients) indexed

# Source
source_type                 enum (ambient_phone|ambient_device|manual_upload)
stt_provider                varchar (whisper|google|deepgram|ios_native)
audio_duration_seconds      integer
audio_file_path             varchar nullable (encrypted, deleted after processing)

# Content
raw_transcript              text (full transcript)
diarized_transcript         jsonb nullable (speaker-labeled segments)
# e.g. [{"speaker": "doctor", "start": 0.5, "end": 12.3, "text": "..."}]

# AI Processing
entities_extracted          jsonb nullable (symptoms, meds, diagnoses by Claude)
soap_note                   jsonb nullable (Subjective/Objective/Assessment/Plan)
summary                     text nullable (patient-friendly summary)
processing_status           enum (pending|processing|completed|failed)

# Consent
patient_consent_given       boolean
consent_timestamp           timestamp

# Audit
created_at                  timestamp
updated_at                  timestamp
```

### chat_sessions + chat_messages (post-visit Q&A)

```
# chat_sessions
id                          uuid PK
patient_id                  uuid FK(patients) indexed
visit_id                    uuid FK(visits) indexed
topic                       varchar nullable
status                      enum (active|completed|escalated)
initiated_at                timestamp
completed_at                timestamp nullable
created_at                  timestamp

# chat_messages
id                          uuid PK
session_id                  uuid FK(chat_sessions) indexed
sender_type                 enum (patient|ai|doctor|system)
message_text                text
referenced_entities         jsonb nullable (condition_ids, medication_ids, observation_ids)
extracted_entities          jsonb nullable (AI-extracted: symptoms, meds, concerns)
ai_model_used               varchar nullable (e.g. "claude-opus-4-6")
ai_prompt_tokens            integer nullable
ai_completion_tokens        integer nullable
created_at                  timestamp
```

### visit_notes (Composition / SOAP)

```
id                          uuid PK
visit_id                    uuid FK(visits) unique indexed
patient_id                  uuid FK(patients) indexed
author_practitioner_id      uuid FK(practitioners)
composition_type            enum (progress_note|discharge_summary|clinic_note)
status                      enum (preliminary|final|amended)

# SOAP Sections
chief_complaint             text nullable
history_of_present_illness  text nullable
review_of_systems           text nullable
physical_exam               text nullable
assessment                  text nullable
assessment_codes            jsonb nullable (ICD-10/SNOMED codes)
plan                        text nullable
follow_up                   text nullable
follow_up_timeframe         varchar nullable

# Specialty sections
additional_sections         jsonb nullable

# Signing
is_signed                   boolean default false
signed_at                   timestamp nullable

# Audit
created_at                  timestamp
updated_at                  timestamp
```

### audit_logs (HIPAA required)

```
id                          uuid PK (immutable)
user_id                     uuid FK(users)
user_role                   varchar (snapshot)
action_type                 enum (create|read|update|delete|download|export|login|logout)
resource_type               varchar (patient|visit|observation|prescription|...)
resource_id                 uuid
success                     boolean
ip_address                  varchar (encrypted)
session_id                  uuid
phi_accessed                boolean
phi_elements                jsonb nullable (what PHI was touched)
accessed_at                 timestamp (immutable, UTC)

INDEX(user_id, accessed_at)
INDEX(resource_id, accessed_at)
INDEX(phi_accessed)
```

### consents (GDPR)

```
id                          uuid PK
patient_id                  uuid FK(patients) indexed
consent_type                enum (privacy|data_sharing|research|telehealth|recording)
status                      enum (active|withdrawn|expired)
consented_at                timestamp
withdrawn_at                timestamp nullable
expires_at                  date nullable
version                     varchar
created_at                  timestamp
```

### users + roles (auth)

```
# users
id                          uuid PK
email                       varchar unique
password_hash               varchar
role_id                     uuid FK(roles)
patient_id                  uuid FK(patients) nullable
practitioner_id             uuid FK(practitioners) nullable
is_active                   boolean default true
last_login_at               timestamp nullable
created_at                  timestamp
updated_at                  timestamp

# roles
id                          uuid PK
name                        varchar (patient|doctor|admin|nurse)
permissions                 jsonb (array of permission strings)
```

## Relationships diagram

```
patients ──1:n──► visits ──1:n──► observations
    │                 │               │
    │                 ├──1:n──► conditions
    │                 ├──1:n──► prescriptions ──n:1──► medications
    │                 ├──1:n──► documents
    │                 ├──1:1──► visit_notes
    │                 ├──1:1──► transcripts
    │                 └──1:n──► chat_sessions ──1:n──► chat_messages
    │
    ├──1:n──► consents
    └──1:1──► users

practitioners ──1:n──► visits
         │
         ├──1:n──► prescriptions
         ├──1:n──► visit_notes
         └──n:1──► organizations

medications ──1:n──► medication_interactions (self-referencing)

users ──1:n──► audit_logs
```

## Open source data sources

| Source | License | What it provides | Sync |
|--------|----------|---------|------|
| RxNorm (NLM) | Public domain | Drug names, codes, forms | RxNav REST API, monthly |
| DrugBank Open | CC0 | Drug interactions, targets | XML download, quarterly |
| LOINC | CC-BY | Lab test codes | CSV download, quarterly |
| SNOMED CT | Free in US | Clinical terminology | NLM download, 2x/year |
| ICD-11 | CC-BY-ND | Diagnosis codes | WHO API |
| OpenFDA | Public domain | Drug labels, adverse events | REST API, daily |
| DailyMed | Public domain | FDA drug labels | XML/JSON API |

## Recommendation: PostgreSQL

Why PostgreSQL over MySQL:
- **jsonb** — native indexing and querying on `specialty_data`, `extracted_entities`
- **tsvector** — full-text search on transcripts and clinical notes
- **UUID** — native type (not varchar)
- **Partitioning** — audit_logs partitioned by month
- HIPAA/SOC2: PostgreSQL is the standard in healthcare

## For demo (hackathon)

**Minimum tables:**
1. patients, practitioners, users, roles
2. visits
3. transcripts
4. observations (a few lab results)
5. conditions (PVCs)
6. medications + prescriptions (propranolol)
7. chat_sessions + chat_messages
8. audit_logs

**Not for demo:**
- medication_interactions (seeding from DrugBank — too much work)
- diagnostic_reports (overkill)
- consents (will be shown in SECURITY.md, not in UI)
- documents (files — too much infrastructure)
