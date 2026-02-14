# PostVisit.ai — Agentic Capabilities Architecture

> How external AI agents can interact with PostVisit while maintaining patient data security.

## 1. Vision

PostVisit.ai is not just a patient-facing app — it is an **agent-native health data platform**. Any action a human user can perform, an authorized AI agent should also be able to perform through well-defined interfaces. This enables:

- **Care coordination agents** (hospital discharge planners, pharmacy bots) to push data into the patient's PostVisit context
- **Doctor-side agents** (clinical decision support, triage AI) to query the patient's post-visit status
- **Patient-side agents** (personal health assistants, insurance bots) to read visit summaries and ask questions on the patient's behalf
- **EHR integration agents** (RSB, CoZo, IKP connectors) to sync health records bidirectionally

## 2. Current API Surface (Agent-Ready)

PostVisit already exposes ~40 REST endpoints under `/api/v1/`. These are immediately usable by agents via Sanctum token authentication.

### 2.1 Read Operations (Low Risk)

| Capability | Endpoint | PHI Level | Agent Use Case |
|-----------|----------|-----------|----------------|
| Patient profile | `GET /patients/{id}` | High | Personal health assistant reads demographics |
| Visit summary | `GET /visits/{id}/summary` | High | Insurance agent reads visit outcome |
| Chat history | `GET /visits/{id}/chat/history` | High | Follow-up agent reviews patient questions |
| Observations | `GET /patients/{id}/observations` | Medium | Wearable sync agent reads vitals |
| Conditions | `GET /patients/{id}/conditions` | High | Clinical decision support reads diagnoses |
| Prescriptions | `GET /patients/{id}/prescriptions` | High | Pharmacy agent checks active medications |
| Documents | `GET /patients/{id}/documents` | High | EHR sync agent reads uploaded documents |
| Lab results | `GET /patients/{id}/observations` | Medium | Lab integration agent reads results |
| Medication info | `GET /medications/search` | None | Drug lookup (public data) |
| Drug interactions | `GET /medications/{rx}/interactions` | Low | Safety agent checks interactions |

### 2.2 Write Operations (Higher Risk)

| Capability | Endpoint | PHI Level | Agent Use Case |
|-----------|----------|-----------|----------------|
| Ask question | `POST /visits/{id}/chat` | High | Patient agent asks follow-up questions |
| Explain term | `POST /visits/{id}/explain` | Medium | Education agent requests explanations |
| Upload document | `POST /patients/{id}/documents` | High | EHR agent pushes lab results |
| Add condition | `POST /patients/{id}/conditions` | High | Diagnosis sync from hospital EHR |
| Send message | `POST /visits/{id}/messages` | High | Care coordinator sends follow-up |
| Create visit | `POST /visits` | High | Companion scribe agent creates visits |
| Upload transcript | `POST /visits/{id}/transcript` | Critical | Recording agent uploads audio |

### 2.3 Doctor-Side Operations (Privileged)

| Capability | Endpoint | PHI Level | Agent Use Case |
|-----------|----------|-----------|----------------|
| Patient list | `GET /doctor/patients` | High | Triage agent scans patient panel |
| Alerts | `GET /doctor/alerts` | High | Monitoring agent reads escalations |
| Chat audit | `GET /doctor/patients/{id}/chat-audit` | Critical | Compliance agent reviews AI responses |
| Engagement | `GET /doctor/patients/{id}/engagement` | Medium | Follow-up agent checks patient activity |
| Reply to patient | `POST /doctor/messages/{id}/reply` | High | Doctor assistant drafts replies |
| Quick action | `POST /doctor/patients/{id}/quick-action` | High | Triage agent triggers follow-up actions |

## 3. Proposed: MCP Server Interface

Beyond REST, PostVisit can expose an **MCP (Model Context Protocol) server** allowing Claude and other LLM agents to interact with the system natively.

### 3.1 MCP Tools (Proposed)

```
postvisit:patient-summary     — Get patient demographics + active conditions
postvisit:visit-context        — Get full visit context (SOAP, transcript, terms)
postvisit:ask-question         — Ask a question in the patient's chat (with visit context)
postvisit:explain-term         — Get plain-language explanation of a medical term
postvisit:check-interactions   — Check drug-drug interactions for patient's medications
postvisit:get-observations     — Read vitals and lab results
postvisit:escalation-check     — Evaluate a message for clinical urgency
postvisit:upload-document      — Push a document (lab result, referral) into patient's file
postvisit:doctor-alerts        — List pending alerts for a practitioner
```

### 3.2 MCP Resources (Proposed)

```
postvisit://patient/{id}/profile          — Patient demographics
postvisit://patient/{id}/medications      — Active medication list
postvisit://patient/{id}/conditions       — Active conditions
postvisit://visit/{id}/summary            — Visit summary (SOAP note)
postvisit://visit/{id}/transcript         — Visit transcript
postvisit://visit/{id}/chat               — Chat history
postvisit://doctor/{id}/dashboard         — Doctor's patient panel
```

## 4. Security Architecture for Agent Access

### 4.1 Authentication: Scoped API Tokens

Current Sanctum cookie auth is designed for browser SPAs. For agent access, we need **scoped bearer tokens**:

```
Authorization: Bearer pvt_abc123...
```

**Token scopes** (principle of least privilege):

| Scope | Access Level | Example Agent |
|-------|-------------|---------------|
| `patient:read` | Read own patient data | Personal health assistant |
| `patient:write` | Modify own patient data | EHR sync agent |
| `patient:chat` | Send/read chat messages | Patient Q&A bot |
| `visit:read` | Read visit summaries | Insurance verification |
| `visit:write` | Create visits, upload audio | Companion scribe |
| `doctor:read` | Read patient panel | Clinical decision support |
| `doctor:write` | Reply, quick actions | Doctor assistant |
| `doctor:audit` | Read audit logs | Compliance bot |
| `medications:read` | Drug info (non-PHI) | Any agent |

**Token properties:**
- Patient tokens can ONLY access their own data (enforced at model level)
- Doctor tokens are scoped to their patient panel
- Tokens expire after 24h by default, configurable per agent
- Each token tied to an `agent_id` for audit trail

### 4.2 Audit Trail: Agent-Aware

The existing `AuditMiddleware` already logs every PHI access. For agents, we extend it:

```php
// Current audit fields (already implemented)
'user_id'       => $user->id,
'user_role'     => $user->role,
'action_type'   => 'read|create|update|delete',
'resource_type' => 'visit|patient|document|...',
'phi_accessed'  => true,
'phi_elements'  => ['visit_data', 'clinical_notes'],

// Proposed agent-specific fields
'agent_id'      => $token->agent_id,        // Which agent made this request
'agent_name'    => $token->agent_name,       // Human-readable agent name
'agent_scope'   => $token->scopes,           // What scopes were used
'agent_purpose' => $request->header('X-Agent-Purpose'),  // Why the agent accessed this
```

This gives compliance officers a complete picture: **who** (agent), **what** (resource), **when** (timestamp), **why** (purpose header).

### 4.3 Consent Model: Patient-Controlled

Agents MUST NOT access patient data without explicit consent:

```
┌──────────────────────────────────────────────┐
│ Patient Consent Flow                          │
│                                               │
│ 1. Agent requests access via OAuth-style flow │
│ 2. Patient sees: "MyPharmacy Bot wants to     │
│    read your medications and conditions"      │
│ 3. Patient approves specific scopes           │
│ 4. Token issued with approved scopes only     │
│ 5. Patient can revoke anytime from Settings   │
└──────────────────────────────────────────────┘
```

**Consent storage:**
- Per-patient, per-agent consent records
- Granular scope control (read meds: yes, read chat: no)
- Time-limited consents (e.g., "allow for 30 days")
- Revocation takes effect immediately (token invalidated)

### 4.4 Data Minimization

Agents receive **only the data they need**, never the full patient record:

- `patient:read` scope returns demographics but NOT chat history
- `visit:read` scope returns the summary but NOT raw audio
- `medications:read` returns drug names but NOT prescriber details
- Document downloads require explicit `document:download` scope
- Transcript audio requires `transcript:audio` scope (highest sensitivity)

### 4.5 Rate Limiting and Abuse Prevention

```php
// Per-agent rate limits (stricter than human users)
'agent:read'  => '100 requests/minute',
'agent:write' => '20 requests/minute',
'agent:chat'  => '5 messages/minute',

// Anomaly detection
- Alert if agent reads >50 patients in 1 hour (data scraping)
- Alert if agent sends >20 chat messages in 10 minutes (abuse)
- Alert if agent accesses data outside normal hours
```

## 5. Agent Interaction Patterns

### 5.1 Pattern: EHR Sync Agent (RSB/CoZo/IKP)

```
EHR Network          PostVisit                Patient
    │                    │                       │
    │──── push labs ────>│                       │
    │    (via webhook)   │── notify ────────────>│
    │                    │   "New lab results"   │
    │                    │                       │
    │<── pull summary ──│                       │
    │   (patient visit) │                       │
```

**Security:** EHR agents use institution-level tokens with `patient:write` scope. Each push creates an audit record. Patient must have pre-authorized the EHR connection.

### 5.2 Pattern: Patient Health Assistant

```
Patient ──> "What were my lab results?"
                │
        Personal Health Agent
                │
    ┌───────────┴───────────┐
    │ postvisit:patient-summary │
    │ postvisit:get-observations│
    └───────────┬───────────┘
                │
    "Your hemoglobin was 13.2 g/dL,
     within normal range (12-16)."
```

**Security:** Patient's own agent uses patient-scoped token. Can only read, not modify. Chat messages are logged but attributed to the agent.

### 5.3 Pattern: Doctor Triage Agent

```
Doctor's AI Assistant
        │
    ┌───┴───────────────────┐
    │ postvisit:doctor-alerts│
    │ postvisit:visit-context│
    └───┬───────────────────┘
        │
    "3 patients need attention:
     - Alex D.: elevated BP trend
     - Maria K.: missed medication
     - Jan N.: escalation flag"
```

**Security:** Doctor-scoped token, `doctor:read` only. Cannot modify patient records or send messages without `doctor:write`.

### 5.4 Pattern: Drug Safety Agent (Autonomous)

```
Patient adds new medication
        │
    EscalationDetector (existing)
        │
    PostVisit checks interactions
        │
    ┌───┴───────────────┐
    │ If critical:       │
    │ - Alert patient    │
    │ - Alert doctor     │
    │ - Log in audit     │
    └───────────────────┘
```

**Security:** Internal agent — no external token needed. Uses existing `ClinicalReasoningPipeline` and `EscalationDetector`. All actions audited.

## 6. Compliance Considerations

### 6.1 HIPAA (US)

- **Business Associate Agreement (BAA):** Required for any external agent service accessing PHI
- **Minimum Necessary Rule:** Scoped tokens enforce this at the API level
- **Audit trail:** Already implemented, extended for agent identification
- **Breach notification:** Agent access patterns monitored for anomalies

### 6.2 GDPR (EU/Belgium)

- **Lawful basis:** Patient consent (explicit, granular, revocable)
- **Data minimization:** Scoped tokens + filtered responses
- **Right to access:** Patient can see which agents accessed their data (via audit log)
- **Right to erasure:** Revoking agent consent deletes the agent's token and cached data
- **Data Processing Agreement (DPA):** Required for agent service providers

### 6.3 Belgian eHealth Platform

- RSB/RSW/CoZo integration must go through the **federal eHealth platform** as intermediary
- Authentication via **eHealth certificates** (not simple API tokens)
- Data exchange uses **KMEHR standard** (Belgian health data format)
- Patient consent managed via **eHealth consent service**

## 7. Implementation Roadmap

### Phase 1: Token-Based Agent Auth (MVP)
- [ ] Add `agent_tokens` table (agent_id, name, scopes, expires_at)
- [ ] Extend Sanctum to support scoped bearer tokens for agents
- [ ] Add `agent_id` / `agent_name` fields to AuditLog
- [ ] Add `X-Agent-Purpose` header parsing in AuditMiddleware
- [ ] Settings UI: "Connected Agents" section showing active tokens

### Phase 2: MCP Server
- [ ] Implement MCP server exposing read-only tools
- [ ] `postvisit:patient-summary`, `postvisit:visit-context`, `postvisit:get-observations`
- [ ] `postvisit:check-interactions`, `postvisit:explain-term`
- [ ] MCP auth via scoped tokens (same as Phase 1)

### Phase 3: Write Operations + EHR Sync
- [ ] MCP write tools: `postvisit:ask-question`, `postvisit:upload-document`
- [ ] Webhook receiver for EHR push notifications
- [ ] KMEHR parser for Belgian health data import
- [ ] Consent management UI for patients

### Phase 4: Autonomous Agents
- [ ] Internal medication safety agent (auto-check on prescription changes)
- [ ] Follow-up reminder agent (nudge patients who haven't engaged)
- [ ] Doctor briefing agent (morning summary of patient panel)

## 8. Key Design Principles

1. **Agent parity:** Anything a user can do, an agent can do (with proper authorization)
2. **Visibility parity:** Anything a user can see, an agent can see (with proper scoping)
3. **Audit everything:** Every agent action is logged with full context
4. **Patient controls:** Patient can see, approve, and revoke all agent access
5. **Least privilege:** Agents get the minimum data needed for their function
6. **Fail safe:** If consent is unclear, deny access. If scope is ambiguous, deny access.
7. **No silent agents:** Patients are always informed when an agent accesses their data
