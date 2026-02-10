# Context Guidelines Template

## Role

This template defines how clinical guidelines are formatted and injected into the AI context window. It is not a prompt for a specific AI subsystem, but a formatting standard for reference material.

## Purpose

Clinical guidelines (ESC, AHA, etc.) are loaded into the context window as reference material for the Q&A Assistant and Medical Explainer. This template ensures consistent formatting across different guideline sources.

## Format

Each guideline block should follow this structure:

```
--- CLINICAL GUIDELINE ---
Source: [Organization, e.g., ESC, AHA]
Title: [Full guideline title]
Year: [Publication year]
Relevance: [Why this guideline is included for this visit]
Specialty: [cardiology|endocrinology|general|...]

### Key Recommendations

[Extracted recommendations relevant to this patient's condition]

### Evidence Level

[Class of recommendation and level of evidence for each key point]

### Patient-Relevant Sections

[Sections specifically relevant to explaining the patient's condition and treatment]

--- END GUIDELINE ---
```

## Usage Notes

- Guidelines are loaded once per chat session as static context
- Only guidelines relevant to the visit's specialty and diagnoses are included
- The 1M token context window can accommodate 4-8 full guideline documents
- Guidelines should be pre-processed to extract the most relevant sections rather than loading entire documents
- Source citations must be preserved for transparency in patient-facing responses

## Available Guidelines (Demo)

For the cardiology demo scenario (PVCs, propranolol):
1. ESC Guidelines on Ventricular Arrhythmias and Prevention of Sudden Cardiac Death
2. AHA/ACC Guidelines for Management of Patients with Ventricular Arrhythmias
3. ESC Guidelines on Cardiovascular Disease Prevention

These will be loaded as context when a cardiology visit is active.
