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
DOI: [Digital Object Identifier]
PMID: [PubMed ID]
URL: [Direct link to publication]
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

## Citation Requirements

Every medical reference MUST include at least one of:
- **PMID** — PubMed ID (e.g., `37622666`)
- **DOI** — Digital Object Identifier (e.g., `10.1093/eurheartj/ehad195`)

References without PMID or DOI are considered unverified and should not be cited in patient-facing responses.

Citation format for responses:
```
(McDonagh TA et al., Eur Heart J 2023; PMID: 37622666)
```

## Usage Notes

- Guidelines are loaded once per chat session as static context
- Only guidelines relevant to the visit's specialty and diagnoses are included
- The 1M token context window can accommodate 4-8 full guideline documents
- Guidelines should be pre-processed to extract the most relevant sections rather than loading entire documents
- Source citations must be preserved for transparency in patient-facing responses
- All PMID references can be verified at runtime via PubMed E-utilities API

## Available Guidelines (Demo — Cardiology)

For the cardiology demo scenario (PVCs, propranolol):

1. **ESC Guidelines on Ventricular Arrhythmias and Prevention of SCD**
   - Zeppenfeld K et al., Eur Heart J 2022
   - DOI: 10.1093/eurheartj/ehac262 | PMID: 36017572

2. **2023 ESC Focused Update — Acute and Chronic Heart Failure**
   - McDonagh TA et al., Eur Heart J 2023
   - DOI: 10.1093/eurheartj/ehad195 | PMID: 37622666

3. **2022 AHA/ACC/HFSA Guideline for Heart Failure Management**
   - Heidenreich PA et al., Circulation 2022
   - DOI: 10.1161/CIR.0000000000001063 | PMID: 35363499

4. **ESC/EAS Dyslipidaemia Guidelines 2019**
   - Mach F et al., Eur Heart J 2020
   - DOI: 10.1093/eurheartj/ehz455 | PMID: 31504418

5. **ESC CVD Prevention Guidelines 2021**
   - Visseren FLJ et al., Eur Heart J 2021
   - DOI: 10.1093/eurheartj/ehab484 | PMID: 34458905

These are loaded as context when a cardiology visit is active.
