# PostVisit.ai API Reference

Base URL: `/api/v1/`

All responses follow the format: `{ "data": ... }` for success, `{ "error": { "message": "..." } }` for errors.

## Authentication

Cookie-based (Sanctum SPA) or Bearer token. For API clients, include the token:
```
Authorization: Bearer <token>
```

For SPA, fetch CSRF cookie first:
```
GET /sanctum/csrf-cookie
```

---

## Auth

### POST /auth/register
Create a new user account.

**Body:**
| Field | Type | Required | Notes |
|-------|------|----------|-------|
| name | string | yes | |
| email | string | yes | unique |
| password | string | yes | min 8 chars |
| password_confirmation | string | yes | must match |
| role | string | yes | `patient` or `doctor` |

**Response:** `201` `{ data: { user, token } }`

### POST /auth/login
Authenticate and receive a token.

**Body:**
| Field | Type | Required |
|-------|------|----------|
| email | string | yes |
| password | string | yes |

**Response:** `200` `{ data: { user, token } }`

### POST /auth/logout
Revoke current access token. **Requires auth.**

**Response:** `200` `{ data: { message: "Logged out successfully." } }`

### GET /auth/user
Get authenticated user profile with patient/practitioner relationships. **Requires auth.**

**Response:** `200` `{ data: { id, name, email, role, patient, practitioner } }`

---

## Patients

All patient endpoints require authentication.

### GET /patients/{patient}
Get patient profile with conditions and prescriptions.

**Response:** `200` `{ data: { id, first_name, last_name, ..., conditions, prescriptions } }`

### PATCH /patients/{patient}
Update patient profile. *(Not yet implemented)*

### GET /patients/{patient}/visits
List all visits for a patient, ordered by most recent.

**Response:** `200` `{ data: [{ id, visit_type, reason_for_visit, started_at, practitioner, organization }] }`

### GET /patients/{patient}/conditions
List all conditions for a patient.

**Response:** `200` `{ data: [{ id, code, code_display, clinical_status, severity }] }`

### POST /patients/{patient}/conditions
Add a condition to a patient. *(Not yet implemented)*

### GET /patients/{patient}/health-record
Comprehensive health record: conditions, prescriptions with medications, recent visits.

**Response:** `200` `{ data: { conditions, prescriptions, visits } }`

### GET /patients/{patient}/documents
List all documents for a patient.

**Response:** `200` `{ data: [{ id, title, document_type, document_date }] }`

### POST /patients/{patient}/documents
Upload a document. *(Not yet implemented)*

### GET /patients/{patient}/prescriptions
List all prescriptions for a patient with medication and visit details.

**Response:** `200` `{ data: [{ id, dose_quantity, dose_unit, frequency, medication, visit }] }`

### GET /patients/{patient}/prescriptions/interactions
Check drug interactions between patient's active prescriptions.

**Response:** `200` `{ data: [{ drug_a_id, drug_b_id, severity, description, management }] }`

---

## Documents

### GET /documents/{document}
View a single document with patient and visit info. **Requires auth.**

**Response:** `200` `{ data: { id, title, document_type, patient, visit } }`

---

## Visits

All visit endpoints require authentication.

### POST /visits
Create a new visit.

**Body:**
| Field | Type | Required | Notes |
|-------|------|----------|-------|
| patient_id | uuid | yes | must exist |
| practitioner_id | uuid | yes | must exist |
| organization_id | uuid | no | |
| visit_type | string | yes | office_visit, telehealth, etc. |
| reason_for_visit | string | yes | |
| started_at | datetime | yes | ISO 8601 |

**Response:** `201` `{ data: { id, visit_type, visit_status: "in_progress", ... } }`

### GET /visits/{visit}
Full visit with all relationships: patient, practitioner, organization, observations, conditions, prescriptions, documents, transcript, visit note.

**Response:** `200` `{ data: { id, ..., observations, conditions, prescriptions, visit_note } }`

The `visit_note` object includes a `medical_terms` field (jsonb) containing extracted medical terms with character offsets, keyed by SOAP section name. Each entry contains `term` (string), `start` (int, 0-based index), and `end` (int, one past last character). The frontend uses these offsets to render clickable highlighted terms.

### GET /visits/{visit}/summary
Condensed visit summary with selected fields for display.

**Response:** `200` `{ data: { patient, practitioner, conditions, prescriptions, visit_note } }`

---

## Transcripts

All transcript endpoints require authentication.

### POST /visits/{visit}/transcript
Upload a transcript for a visit.

**Body:**
| Field | Type | Required | Notes |
|-------|------|----------|-------|
| raw_transcript | string | yes | Full text |
| source_type | string | yes | `ambient_phone`, `ambient_device`, `manual_upload` |
| stt_provider | string | no | defaults to `none` |
| audio_duration_seconds | integer | no | defaults to `0` |
| patient_consent_given | boolean | yes | must be true |

**Response:** `201` `{ data: { id, processing_status: "pending" } }`

### GET /visits/{visit}/transcript
View the transcript for a visit.

**Response:** `200` `{ data: { raw_transcript, soap_note, summary, processing_status } }`

### POST /visits/{visit}/transcript/process
Trigger AI processing of the transcript.

**Response:** `200` `{ data: { processing_status: "processing", transcript_id } }`

### GET /visits/{visit}/transcript/status
Check transcript processing status.

**Response:** `200` `{ data: { processing_status, has_soap_note, has_entities, has_summary } }`

---

## Chat (AI Q&A)

All chat endpoints require authentication.

### POST /visits/{visit}/chat
Send a message and receive AI response. Creates a chat session if none exists.

**Body:**
| Field | Type | Required | Notes |
|-------|------|----------|-------|
| message | string | yes | max 5000 chars |

**Response:** `200` `{ data: { user_message, ai_message, session_id } }`

### GET /visits/{visit}/chat/history
Get chat history for a visit.

**Response:** `200` `{ data: { session, messages: [{ sender_type, message_text, created_at }] } }`

---

## Explain

### POST /visits/{visit}/explain
Get a plain-language explanation of a medical term in visit context. **Requires auth.**

**Body:**
| Field | Type | Required |
|-------|------|----------|
| term | string | yes |
| context | string | no |

**Response:** `200` `{ data: { term, explanation, visit_context } }`

---

## Medications

All medication endpoints require authentication.

### GET /medications/search?q={query}
Search medications by name or RxNorm code. Minimum 2 characters.

**Response:** `200` `{ data: [{ rxnorm_code, generic_name, display_name, form }] }`

### GET /medications/{rxnormCode}
Get medication details by RxNorm code.

**Response:** `200` `{ data: { rxnorm_code, generic_name, display_name, form, strength_value } }`

### GET /medications/{rxnormCode}/interactions
Get known interactions for a medication.

**Response:** `200` `{ data: [{ severity, description, management, drugA, drugB }] }`

---

## Feedback / Messages

All feedback endpoints require authentication.

### POST /visits/{visit}/messages
Send feedback to the visit practitioner.

**Body:**
| Field | Type | Required |
|-------|------|----------|
| title | string | yes |
| body | string | yes |

**Response:** `201` `{ data: { id, type: "patient_feedback" } }`

### GET /visits/{visit}/messages
List all messages (patient feedback + doctor replies) for a visit.

**Response:** `200` `{ data: [{ type, title, body, created_at, read_at }] }`

### PATCH /messages/{message}/read
Mark a message as read.

**Response:** `200` `{ data: { id, read_at } }`

---

## Doctor Dashboard

All doctor endpoints require `auth:sanctum` + `role:doctor,admin`.

### GET /doctor/dashboard
Overview: recent visits, patient count, unread notifications.

**Response:** `200` `{ data: { recent_visits, stats: { total_patients, total_visits, unread_notifications } } }`

### GET /doctor/patients
List patients the doctor has seen.

**Response:** `200` `{ data: [{ id, first_name, last_name, visits_count }] }`

### GET /doctor/patients/{patient}
Patient detail with active conditions and current prescriptions.

**Response:** `200` `{ data: { id, first_name, last_name, conditions, prescriptions } }`

### GET /doctor/patients/{patient}/visits
All visits for a specific patient.

**Response:** `200` `{ data: [{ id, visit_type, started_at, practitioner, organization }] }`

### GET /doctor/patients/{patient}/engagement
Patient engagement stats: chat session count, total messages.

**Response:** `200` `{ data: { chat_sessions, total_messages, total_sessions } }`

### GET /doctor/patients/{patient}/chat-audit
Full chat audit trail: all sessions with messages.

**Response:** `200` `{ data: [{ id, topic, status, messages: [...] }] }`

### GET /doctor/notifications
Doctor's notifications, ordered by most recent.

**Response:** `200` `{ data: [{ id, type, title, body, visit, read_at }] }`

### POST /doctor/messages/{message}/reply
Reply to a patient message.

**Body:**
| Field | Type | Required |
|-------|------|----------|
| body | string | yes |

**Response:** `201` `{ data: { id, type: "doctor_reply" } }`

---

## Medical References

All reference endpoints require authentication.

### GET /references
List medical references with optional filters.

**Query params:**
| Param | Type | Notes |
|-------|------|-------|
| specialty | string | Filter by specialty (cardiology, etc.) |
| category | string | guideline, meta_analysis, rct, review |
| verified | boolean | Only verified references |

**Response:** `200` `{ data: [{ id, title, authors, journal, year, doi, pmid, url, source_organization, category, specialty, verified }] }`

### GET /references/{reference}
Get a single reference by ID.

**Response:** `200` `{ data: { id, title, authors, journal, year, doi, pmid, url, verified, verified_at } }`

### POST /references/{reference}/verify
Verify a reference against PubMed and update its verification status.

**Response:** `200` `{ data: { reference, pubmed_result, verified } }`

### POST /references/verify-pmid
Verify a PMID exists in PubMed without storing it.

**Body:**
| Field | Type | Required |
|-------|------|----------|
| pmid | string | yes |

**Response:** `200` `{ data: { exists, pmid, title, authors, journal, year, doi, url } }`

---

## Audit

Requires `role:doctor,admin`.

### GET /audit/logs
Paginated audit logs with optional filters.

**Query params:**
| Param | Type | Notes |
|-------|------|-------|
| resource_type | string | Filter by type (visit, patient, etc.) |
| user_id | uuid | Filter by user |
| action_type | string | create, read, update, delete, login, etc. |
| per_page | integer | Default 50 |

**Response:** `200` `{ data: { data: [...], current_page, last_page, total } }`

---

## Demo

Demo endpoints do not require authentication.

### POST /demo/start
Start a demo session. Returns auth token and demo visit.

**Body (optional):**
| Field | Type | Default |
|-------|------|---------|
| role | string | `patient` |

**Response:** `200` `{ data: { user, token, visit, role } }`

### GET /demo/status
Check if demo data is seeded.

**Response:** `200` `{ data: { seeded, patient_email, doctor_email, password } }`

### POST /demo/reset
Reset database and re-seed demo data. **Destructive.**

**Response:** `200` `{ data: { message: "Demo data has been reset successfully." } }`

### POST /demo/simulate-alert
Simulate an escalation alert for the doctor.

**Response:** `200` `{ data: { message: "Escalation alert simulated successfully." } }`

---

## Error Responses

| Status | Meaning |
|--------|---------|
| 401 | Unauthenticated |
| 403 | Forbidden (wrong role) |
| 404 | Resource not found |
| 422 | Validation error |
| 500 | Server error |

Validation errors return: `{ message: "...", errors: { field: ["error message"] } }`
