# Connectors & Health Data Integration — Research

> Research from 2026-02-10. Context: how PostVisit.ai can integrate with existing health records systems.

## Key Takeaway: FHIR Is the Common Language

Regardless of the platform (Apple, Google, hospital EHR) — the standard that connects everything is **FHIR R4** (Fast Healthcare Interoperability Resources). REST API + JSON, a natural fit for Laravel. If PostVisit.ai speaks FHIR, it can communicate with virtually any medical system.

## Apple Health

### HealthKit (fitness: steps, heart rate, sleep)
- Available **ONLY** from a native iOS app (Swift)
- Zero chance of access from a web app
- Alternatives: bridging services (Terra API, OneTwentyOne) — REST API to HealthKit via a native intermediary

### Health Records (clinical data)
- Uses **FHIR R4** — accessible via **SMART on FHIR OAuth**
- A web app CAN access clinical data through FHIR
- 7 data categories: medications (RxNorm), lab results (LOINC), immunizations (CVX), conditions (SNOMED), allergies, procedures (CPT/SNOMED), vitals (LOINC)
- Plus: clinical notes, COVID results

### Integration Requirements
- Implement SMART on FHIR OAuth (patient username/password + access tokens >=10min + refresh tokens >=3 months)
- FHIR R4 API endpoint
- For demo: **mock FHIR data is sufficient** — connecting to a real hospital takes weeks

## Google Health

### Health Connect (Android)
- Local on-device database, no web API
- Android equivalent of HealthKit — requires a native app

### Google Cloud Healthcare API
- Full **FHIR store** with HIPAA compliance
- Enterprise-level, expensive, but production-ready
- Supports FHIR R4, HL7v2, DICOM (imaging)

### Google Fit API
- **Deprecated** — sunsetting in 2026, do not build on this

## Anthropic — Claude for Healthcare (January 2026)

### What They Launched
- **Claude for Healthcare** — a dedicated healthcare toolkit
- **BAA available** on Enterprise and first-party API — official HIPAA compliance support
- **Zero-training policy** on medical data — patient data does not train models
- **HealthEx** — first integration with consumer health records
- **Apple Health + Android Health Connect** — planned integration with Claude apps on iOS/Android

### Connectors to Medical Sources
- CMS (Centers for Medicare & Medicaid Services)
- ICD-10 (diagnostic codes)
- NPI Registry (physician registry)
- PubMed (evidence-based medicine)

### Agentic Workflows — Human-in-the-Loop
Three levels of autonomy:
1. **Read-Only** — AI reads, does not act
2. **Drafting** — AI prepares, human approves
3. **Action with Approval** — AI acts after approval

Full audit trail at every level. **This is exactly the doctor-in-the-loop model from PostVisit.ai.**

### Who Is Already Using Claude in Healthcare
- Banner Health (55K employees)
- Elation Health (EHR)
- Carta Healthcare (99% accuracy in clinical data)

## What This Means for PostVisit.ai

### For Demo (Hackathon)
- Mock FHIR data — realistic cardiology scenario
- Architecture demonstrates readiness for real integrations
- Diagram: FHIR as the data exchange standard

### For Production
- FHIR R4 as the native visit data format
- SMART on FHIR OAuth for Apple Health Records
- Google Cloud Healthcare API as an optional FHIR store
- BAA with Anthropic (available on Enterprise)

### Hackathon Argument
PostVisit.ai fits into an ecosystem that Anthropic is **actively building**. This project is not a fantasy — it is exactly aligned with the direction Anthropic took a month ago. Clinical guidelines + 1M context window + doctor-in-the-loop = what Anthropic promotes as the future of healthcare AI.

## FHIR Libraries (Open Source)

| Language | Library | Notes |
|----------|---------|-------|
| PHP | php-fhir (dcarbone/php-fhir) | Generates PHP classes from FHIR definitions |
| JavaScript | fhir-client.js | Browser/Node.js, SMART on FHIR support |
| Java | HAPI FHIR | Full server/client, gold standard |
| Python | SMART on FHIR client | Client with OAuth |
| Cloud | Google Cloud Healthcare API | Managed FHIR store |

For Laravel: **php-fhir** + custom FHIR service layer in `app/Services/Fhir/`.
