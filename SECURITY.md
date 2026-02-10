# Security

> **Disclaimer**: PostVisit.ai is a prototype built for the "Built with Opus 4.6" hackathon (February 2026). It is **not intended for clinical use** and does **not** process real patient data. This document describes the security architecture designed for production readiness.

## Data Classification

PostVisit.ai handles two categories of data:

**Protected Health Information (PHI)** — visit transcripts, medical records, prescriptions, lab results, diagnosis details, and any data that can identify a patient in a clinical context.

**Non-PHI** — application configuration, clinical guidelines (public medical literature), UI preferences, anonymized analytics.

Every component in the system is aware of this distinction. PHI fields are explicitly marked in the data model and subject to encryption, access control, and audit logging.

## Trust Boundaries

The system enforces security at four trust boundaries:

```
[Patient App] ──TLS──▶ [API Gateway] ──authz──▶ [Application Core] ──encrypted──▶ [Database]
[Doctor App]  ──TLS──▶ [API Gateway]                    │
                                                         │ data minimization
                                                         ▼
                                                  [Anthropic API]
```

1. **Client → API Gateway**: All traffic over TLS 1.2+. Authentication via short-lived tokens. No PHI in URL parameters or query strings.
2. **API Gateway → Application Core**: Role-based authorization. Patient and doctor tokens grant access only to their own data.
3. **Application Core → Database**: PHI columns encrypted at rest using AES-256. Application-level encryption ensures that database access alone does not expose patient data.
4. **Application Core → Anthropic API**: Data minimization — only the clinical context necessary for the patient's question is sent. Direct patient identifiers (name, date of birth, insurance ID) are stripped before the prompt is assembled.

## Access Control

**Patient** — can view only the visit they are authorized for. Cannot access other patients' data or doctor-facing dashboards.

**Doctor** — can view visits for their own patients only. Has access to the feedback dashboard showing patient questions and AI-generated explanations.

**System** — no shared admin account. Administrative operations require explicit, auditable authorization.

Access control is enforced at the API layer (middleware) and at the data layer (query scoping), providing defense in depth.

## Encryption

| Layer | Method | Scope |
|-------|--------|-------|
| In transit | TLS 1.2+ (Let's Encrypt) | All client-server communication |
| At rest (database) | AES-256, application-level | All PHI columns: transcripts, visit notes, prescriptions, lab results |
| At rest (backups) | Encrypted volume | Full database backups |
| API keys & secrets | Environment variables | Never committed to repository (see `.env.example`) |

## Audit Logging

Every access to PHI is logged with:

- **Who**: authenticated user ID and role
- **When**: UTC timestamp
- **What**: resource type and identifier (e.g., `visit:123`, `prescription:456`)
- **Action**: `viewed`, `queried_ai`, `exported`
- **Context**: IP address, session ID

Audit logs are append-only and stored separately from application data. They are not accessible through the patient or doctor interfaces.

## AI Data Handling

PostVisit.ai uses Claude Opus 4.6 for patient-facing explanations. The AI integration follows these principles:

**Data minimization** — The prompt sent to Claude contains only the clinical context relevant to the patient's question (visit notes, prescriptions, relevant test results). Direct identifiers are excluded when not clinically necessary.

**No training on patient data** — Anthropic's API does not use customer data for model training. In production, a Business Associate Agreement (BAA) with Anthropic would be required.

**Guardrails** — The system does not generate new medical advice. When a patient's question suggests a potentially dangerous symptom, the system responds with an escalation message directing them to contact their physician or emergency services.

**Prompt versioning** — System prompts are stored in the `prompts/` directory, version-controlled alongside application code, and subject to the same review process.

## Compliance Readiness

This architecture is designed with the following compliance frameworks in mind:

**HIPAA (US)** — PHI encryption, access controls, audit trail, minimum necessary principle, BAA-ready integration with third-party services.

**GDPR (EU)** — Lawful basis for processing (patient consent), right of access, right to erasure, data minimization, purpose limitation to a single visit context.

**SOC 2 / ISO 27001 / HITRUST** — The separation of concerns, logging infrastructure, and encryption model provide a foundation for future certification.

## Responsible Disclosure

If you discover a security vulnerability, please report it to **security@postvisit.ai**. Do not open a public issue. We will acknowledge receipt within 48 hours.
