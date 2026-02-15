# PostVisit.ai — Working Documentation (hackathon / demo)

## 0. Hackathon Context and Evaluation Criteria
This project is being built **as part of the Built with Opus 4.6 hackathon** and must meet its requirements. Key points to highlight in the repo and demo:
- **Open Source**: everything shown in the demo must be open source (backend, frontend, models, other components).
- **New Work Only**: the project must be built from scratch during the hackathon.
- **Team size**: up to 2 people.
- **Banned Projects**: disqualification if the project violates law/ethics/platform policies, or uses code/data/assets without rights to use them.

Evaluation criteria (which we consciously design the demo and narrative around):
- **Impact (25%)** — real-world potential and value of the solution.
- **Opus 4.6 Use (25%)** — creative use of Opus 4.6 capabilities.
- **Depth & Execution (20%)** — engineering, attention to detail, readiness for growth.
- **Demo (30%)** — quality and appeal of the video presentation.

## 0.1. Problem statements (project alignment)
The hackathon highlights 3 main project directions:
- **Build a Tool That Should Exist** — an AI-native tool that eliminates tedious work.
- **Break the Barriers** — access to expert knowledge and tools for everyone.
- **Amplify Human Judgment** — AI enhances professional judgment without replacing the human.

PostVisit.ai maps to **all three**:
- **Build a Tool That Should Exist**: eliminates the tedious process of sifting through records and context after a visit; organizes what is already in the documentation.
- **Break the Barriers**: gives the patient access to expert knowledge from the visit in plain language, without the barrier of specialized jargon.
- **Amplify Human Judgment**: feedback and context flow back to the doctor (doctor-in-the-loop), and the system does not replace clinical decisions.

## 1. Purpose and Context
PostVisit.ai is a system that maintains the context of a **specific clinical visit** and helps the patient after leaving the office. Its role is to explain and organize information from the visit, not to replace the doctor.

Core assumption: **after a visit, the patient needs clear, simple explanations of what the doctor has already said and documented**. The system is not a general health chatbot and does not operate outside the context of that visit.

## 2. Problem
- After a visit, the patient often does not remember the recommendations.
- The patient does not understand medical terminology.
- During the recovery period, there is no support, and the patient ends up calling back to ask about things related to the same visit.

## 3. Key Differentiator
PostVisit.ai does not give "general" health answers. It is **anchored in one specific visit**, and all answers and explanations derive only from that context.

## 4. Primary user
- **Patient**

## 5. Context Sources
- Discharge summary / recommendations
- Medical documents
- Patient healthcare record
- Conversation transcript (ambient scribing on the patient side)
- Optionally: separate physician scribe (open topic)

## 6. Functional Scope (demo)
### 6.1. Patient Screen (last visit)
The patient screen shows the **last visit** (not only cardiology — eventually multiple specialties). In the demo we use a cardiology scenario, but the structure is shared across all specialties.

Visible sections:
- Visit protocol / description
- Additional tests (e.g. ECHO, ECG, other tests — depending on specialty)
- Doctor's recommendations
- Medications (new / changed / continued)
- Next steps

Every element is clickable and can be explained in plain language.

### 6.2. Post-Visit Q&A (in context)
The patient can ask questions about the visit content:
- Explanation of terms and diagnosis
- Information about medications and interactions
- Clarification of recommendations

The system does not generate new recommendations. In cases of risky symptoms, it should escalate and refer the patient to their doctor.

## 7. Demo Scenario
- Young doctor and young patient
- Patient: premature ventricular complexes (PVCs)
- Recommendations: propranolol, more sleep, less stress
- System:
  - displays recommendations and medications
  - explains terminology
  - maintains the context of a single visit

## 8. Product Language
- Working documentation: **Polish**
- Product / UI: **English** with an "International" option
- Other languages: optionally later

## 9. Stack
- Backend: **Laravel**
- Frontend: **Vue**
- Target model: **Claude Opus 4.6**
- Tests: possible on cheaper models (for cost reasons)

## 10. Compliance and Security (for demo)
This section describes the **approach to compliance** and what we will show in the demo. This is not legal advice or a full analysis.

### 10.1. HIPAA — Minimum Plan (USA)
Assumptions:
- We process medical data from the visit (PHI) — HIPAA applies if we operate with a medical entity in the USA.
- In that case, the system acts as a **Business Associate** and requires a **BAA** with the healthcare facility.

What we need to show in the demo (simulated):
- **Minimum necessary**: the system uses only the data needed to handle the visit.
- **Access control**: separate access for the patient and the doctor.
- **Audit trail**: logging data access (at least in a basic form).
- **Encryption**: data encrypted in transit and at rest (declaration and visible direction in the architecture).
- **Incident response**: indication that the system has incident reporting procedures.

### 10.2. GDPR — Minimum Plan (EU)
Assumptions:
- Personal and medical data are particularly sensitive.

What we need to show in the demo (simulated):
- **Legal basis for processing** (consent / contract).
- **Right to access and deletion** (capability in the UI or declaration in documentation).
- **Data minimization** and purpose limitation to a single visit.

### 10.3. Other Certifications (long-term)
- **SOC 2** (often required by clients in the USA)
- **ISO 27001** (information security management system)
- **HITRUST** (commonly seen in healthcare)

In the demo we do not implement certifications, but we show that the architecture is ready for their requirements.

### 10.4. Product Guardrails
- The system does not issue recommendations beyond what comes from the visit.
- In cases of risky symptoms: a message urging the patient to contact their doctor.
- Information is translated, not "diagnosed".
