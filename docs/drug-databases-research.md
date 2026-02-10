# Drug Databases Research Report for PostVisit.ai

**Date:** 2026-02-10
**Purpose:** Identify the fastest path to a working drug lookup for a hackathon demo -- an AI system that helps patients understand their medications after a clinical visit.

---

## Table of Contents

1. [RxNorm (NIH/NLM)](#1-rxnorm-nihnlm)
2. [OpenFDA (FDA)](#2-openfda-fda)
3. [DailyMed (NLM)](#3-dailymed-nlm)
4. [DrugBank](#4-drugbank)
5. [WHO ATC Classification](#5-who-atc-classification)
6. [Other Notable Sources](#6-other-notable-sources)
7. [Polish Drug Coverage & Mapping](#7-polish-drug-coverage--mapping)
8. [European Drug Coverage (EMA)](#8-european-drug-coverage-ema)
9. [Hackathon Recommendations](#9-hackathon-recommendations)

---

## 1. RxNorm (NIH/NLM)

**Website:** https://lhncbc.nlm.nih.gov/RxNav/APIs/RxNormAPIs.html

### What It Provides
RxNorm is a standardized drug nomenclature system maintained by the US National Library of Medicine. It provides normalized names for clinical drugs and links drug names across many different vocabularies.

### Data Available (FREE)
- Drug names (brand and generic)
- Ingredient lists
- Dose forms and strengths
- NDC (National Drug Code) mappings
- RxCUI (unique concept identifiers)
- Term types (ingredient, brand name, clinical drug, etc.)
- Spelling suggestions for fuzzy matching
- Relationships between drugs (generic <-> brand, ingredient <-> product)

### API Details
- **Base URL:** `https://rxnav.nlm.nih.gov/REST`
- **Format:** JSON (append `.json`) or XML (default)
- **Authentication:** None required (no API key needed)
- **Rate Limit:** 20 requests/second per IP address
- **License:** Non-proprietary, completely free

### Key Endpoints

| Endpoint | Purpose |
|----------|---------|
| `getDrugs?name={name}` | Find drugs by name |
| `findRxcuiByString?name={name}` | Get RxCUI for a drug name |
| `getSpellingSuggestions?name={name}` | Fuzzy matching / typo correction |
| `getAllRelatedInfo?rxcui={id}` | Get all related concepts (ingredients, brands, etc.) |
| `getRxConceptProperties?rxcui={id}` | Get name, term type, synonyms |
| `getApproximateMatch?term={term}` | Approximate string matching |
| `getDrugs?name=amlodipine` | Example: find all amlodipine products |

### Example Query
```
GET https://rxnav.nlm.nih.gov/REST/drugs.json?name=amlodipine
```

### Strengths
- Excellent for normalizing drug names (brand -> generic -> ingredient)
- Spelling suggestions help with typos and partial names
- Completely free, no signup
- Good for mapping between naming systems

### Limitations
- **No clinical content** -- no side effects, interactions, patient descriptions
- US-centric (primarily US drug products)
- The Drug Interaction API was **discontinued in January 2024**
- RxNav-in-a-Box (Docker) requires UMLS license agreement for download

### Patient-Facing Info Quality: LOW
RxNorm is a nomenclature system, not a clinical content database. Use it as a **bridge** to look up drugs in other databases.

---

## 2. OpenFDA (FDA)

**Website:** https://open.fda.gov/apis/drug/

### What It Provides
OpenFDA provides programmatic access to FDA public data including drug labels (package inserts), adverse event reports, and drug product information.

### Data Available (FREE)
- **Drug Labels API** -- full structured product labeling (SPL) data
- **Adverse Events API** -- FAERS reports (side effects reported to FDA)
- **Drug NDC Directory** -- product listings
- **Drugs@FDA** -- approval information

### Drug Label JSON Fields (Most Relevant for PostVisit.ai)

| Field | Content |
|-------|---------|
| `description` | Drug description |
| `indications_and_usage` | What the drug is for |
| `dosage_and_administration` | How to take it |
| `adverse_reactions` | Side effects |
| `warnings` | Safety warnings |
| `warnings_and_cautions` | Additional cautions |
| `boxed_warning` | Black box warnings (serious) |
| `contraindications` | When NOT to use |
| `drug_interactions` | Interactions with other drugs |
| `information_for_patients` | Patient-facing info |
| `patient_medication_information` | Patient medication guide |
| `clinical_pharmacology` | How the drug works |
| `mechanism_of_action` | Mechanism details |
| `openfda.brand_name` | Brand names |
| `openfda.generic_name` | Generic name |
| `openfda.substance_name` | Active ingredient(s) |
| `openfda.route` | Route of administration |
| `openfda.rxcui` | RxNorm CUI (cross-reference!) |

### API Details
- **Base URL:** `https://api.fda.gov/drug/label.json`
- **Format:** JSON
- **Authentication:** API key recommended but not required
- **Rate Limits:**
  - Without API key: 40 requests/minute, 1,000/day
  - With API key (free): 240 requests/minute, 120,000/day
- **License:** Public domain (CC0)

### Example Queries
```bash
# Search by generic name
GET https://api.fda.gov/drug/label.json?search=openfda.generic_name:amlodipine&limit=5

# Search by brand name
GET https://api.fda.gov/drug/label.json?search=openfda.brand_name:norvasc&limit=1

# Get adverse events for a drug
GET https://api.fda.gov/drug/event.json?search=patient.drug.medicinalproduct:amlodipine&limit=10

# Search with specific fields
GET https://api.fda.gov/drug/label.json?search=openfda.generic_name:amlodipine&limit=1
```

### Strengths
- **Best source for patient-relevant content** -- side effects, warnings, dosing, interactions
- Structured JSON, easy to parse
- Free API key gives generous rate limits
- Labels include `information_for_patients` sections
- Cross-references to RxNorm via `openfda.rxcui`
- FAERS adverse event data for real-world side effect frequencies

### Limitations
- **US drugs only** -- FDA-approved products
- Label text is written for professionals, not always patient-friendly
- Some older labels may have less structured data
- Label text can be very long -- needs LLM summarization for patient use

### Patient-Facing Info Quality: MEDIUM-HIGH
Contains `information_for_patients` and `patient_medication_information` fields. The `adverse_reactions` and `warnings` fields are comprehensive but written in medical language -- ideal input for LLM-based simplification.

---

## 3. DailyMed (NLM)

**Website:** https://dailymed.nlm.nih.gov/dailymed/app-support-web-services.cfm

### What It Provides
DailyMed is the official NLM repository for FDA-approved drug labeling (package inserts). It provides the same SPL data as OpenFDA but with additional features like media files and version history.

### Data Available (FREE)
- Full drug labels in SPL format
- Drug names (brand and generic)
- NDC codes
- RxCUI mappings
- Package insert PDFs
- Label media/images
- Version history of label changes

### API Details
- **Base URL:** `https://dailymed.nlm.nih.gov/dailymed/services/v2/`
- **Format:** JSON (append `.json`) or XML (append `.xml`)
- **Authentication:** None
- **Rate Limits:** Not officially documented (be reasonable)
- **License:** Public domain

### Key Endpoints

| Endpoint | Purpose |
|----------|---------|
| `/drugnames.json` | List/search drug names |
| `/spls.json?drug_name={name}` | Search SPLs by drug name |
| `/spls/{SETID}.json` | Get specific SPL document |
| `/spls/{SETID}/media` | Get media for an SPL |
| `/spls/{SETID}/ndcs` | Get NDC codes |
| `/rxcuis.json` | List RxCUI mappings |
| `/ndcs.json` | List NDC codes |

### Example Queries
```bash
# Search drug names
GET https://dailymed.nlm.nih.gov/dailymed/services/v2/drugnames.json?drug_name=amlodipine

# Get SPLs for a drug
GET https://dailymed.nlm.nih.gov/dailymed/services/v2/spls.json?drug_name=amlodipine

# Full bulk download available
https://dailymed.nlm.nih.gov/dailymed/spl-resources-all-drug-labels.cfm
```

### Strengths
- Official source for FDA drug labels
- Includes version history (track label changes)
- Bulk download option for offline use
- Links to RxNorm CUIs

### Limitations
- US drugs only
- SPL format can be complex to parse for specific sections
- Less granular field-level access compared to OpenFDA
- Rate limits undocumented

### Patient-Facing Info Quality: MEDIUM
Same underlying data as OpenFDA. DailyMed is better for downloading full label documents; OpenFDA is better for field-level API queries.

### Verdict: Use OpenFDA instead for the hackathon
OpenFDA provides the same data with better field-level JSON access. Use DailyMed only if you need bulk downloads or version history.

---

## 4. DrugBank

**Website:** https://go.drugbank.com/

### What It Provides
DrugBank is a comprehensive drug database with detailed pharmacological information, drug-drug interactions, and clinical data.

### Free vs. Paid

| Feature | Free | Paid |
|---------|------|------|
| Website search | Yes | Yes |
| Academic dataset (CC BY-NC 4.0) | Yes (non-commercial) | N/A |
| CC0 cross-linking datasets | Yes | N/A |
| Full database download | No | Yes (commercial license) |
| REST API access | No | Yes (paid license) |
| Drug-drug interactions (bulk) | No | Yes |
| Commercial use | No | Yes |

### Free Access Options
- **DrugBank Online** -- search the website for free (no API)
- **Academic datasets** -- downloadable under CC BY-NC 4.0 for non-commercial research
- **CC0 datasets** -- specifically for cross-linking and re-use (public domain)
- Contains 500k+ drug and drug product entries

### API Details (Paid Only)
- REST API, returns JSON
- Drug name search, product lookup, interactions
- Requires commercial license for any production use

### Strengths
- Very high-quality curated data
- Excellent drug-drug interaction data
- International drug coverage (not just US)
- ATC codes included

### Limitations
- **API requires paid license** -- not suitable for a free hackathon project
- Academic license restricts commercial use
- Website scraping is against ToS

### Patient-Facing Info Quality: HIGH (but paywalled)
DrugBank has excellent patient-relevant data, but you cannot access it programmatically for free in a production context.

### Verdict: NOT recommended for hackathon
Unless you can get a quick academic license and it is truly non-commercial, avoid DrugBank for the demo. Use OpenFDA + RxNorm instead.

---

## 5. WHO ATC Classification

**Website:** https://atcddd.fhi.no/atc_ddd_index/

### What It Provides
The Anatomical Therapeutic Chemical (ATC) classification system categorizes drugs by therapeutic area and chemical substance. It is the international standard for drug classification used worldwide, including Poland and the EU.

### Data Available (FREE)
- ATC codes (hierarchical: anatomical group -> therapeutic subgroup -> pharmacological subgroup -> chemical subgroup -> chemical substance)
- DDD (Defined Daily Dose) values
- Searchable online index

### Access Options
- **Free online search:** https://atcddd.fhi.no/atc_ddd_index/
- **GitHub scrape datasets:** https://github.com/fabkury/atcd (CSV files of all ATC classes)
- **R package:** https://github.com/jorainer/atc
- **Paid electronic file:** Full ATC-DDD index as Excel for EUR 200
- **No official REST API**

### Strengths
- International standard -- works for Polish, EU, and US drugs
- Hierarchical classification is very useful for grouping related drugs
- Free to search online; community CSV datasets available
- Essential for mapping between Polish brand names and international standards

### Limitations
- No official API (web scraping or community datasets required)
- The classification is at the substance level, not product level
- No clinical content (no side effects, dosing, etc.)

### Patient-Facing Info Quality: NONE
ATC is a classification system only. Use it as a mapping/grouping tool.

---

## 6. Other Notable Sources

### 6.1 MedlinePlus (NLM)

**Website:** https://medlineplus.gov/druginformation.html

- **What:** Patient-friendly drug information written in plain language (via AHFS)
- **API:** MedlinePlus Connect web service -- accepts RxNorm CUI codes and returns links to patient drug info
- **URL:** `https://medlineplus.gov/medlineplus-connect/web-service/`
- **Format:** XML
- **Cost:** Free
- **Patient Quality: EXCELLENT** -- specifically written for patients in lay language
- **Limitation:** Returns links to MedlinePlus pages, not structured data. US drugs only.
- **Best for:** Getting patient-friendly article links after resolving a drug via RxNorm

### 6.2 PubChem (NCBI/NIH)

**Website:** https://pubchem.ncbi.nlm.nih.gov/

- **What:** Chemical compound database with some pharmacological data
- **API:** PUG REST API (completely free)
- **Format:** JSON, XML, SDF
- **Rate Limit:** 5 requests/second, 400/minute
- **Python Library:** `pubchempy`
- **Patient Quality: LOW** -- chemical/research data, not patient-facing
- **Best for:** Chemical structure data, compound identification; not useful for patient info

### 6.3 DDinter 2.0

**Website:** https://ddinter2.scbdd.com/

- **What:** Comprehensive drug-drug interaction database
- **Data:** 2,310 drugs, 302,516 DDI records with mechanism descriptions and management recommendations
- **Cost:** Free, open-access, no registration
- **API:** Web interface with search/query; no documented REST API
- **Patient Quality: MEDIUM** -- has management strategies and risk levels
- **Limitation:** No documented REST API; web-based access only
- **Best for:** Manual verification of interactions; could be scraped but not ideal for hackathon

### 6.4 OpenReact (FreeMedForms)

**Website:** https://github.com/freemed/openreact

- **What:** Open-source drug interaction API based on FreeMedForms database
- **Cost:** Free (open source)
- **Note:** Small project, may not be actively maintained. Worth investigating as a backup.

---

## 7. Polish Drug Coverage & Mapping

### 7.1 URPL (Urzad Rejestracji Produktow Leczniczych)

**Website:** https://www.gov.pl/web/urpl

The Polish Office for Registration of Medicinal Products maintains the Register of Medicinal Products (Rejestr Produktow Leczniczych - RPL).

#### Access Points

| Source | URL | Format |
|--------|-----|--------|
| Public search portal | https://rejestry.ezdrowie.gov.pl/rpl/search/public | Web UI |
| Medical Registries API | `rejestrymedyczne.ezdrowie.gov.pl/api/rpl/medicinal-products/` | JSON/PDF |
| Open Data portal | https://dane.gov.pl/pl/dataset/397,rejestr-produktow-leczniczych | XML, XLSX, CSV |

#### API Endpoints (Discovered)

```
# Get product characteristic (SmPC) - returns PDF
GET https://rejestrymedyczne.ezdrowie.gov.pl/api/rpl/medicinal-products/{ID}/characteristic

# Get patient leaflet (PIL) - returns PDF
GET https://rejestrymedyczne.ezdrowie.gov.pl/api/rpl/medicinal-products/{ID}/leaflet

# Get packaging info
GET https://rejestrymedyczne.ezdrowie.gov.pl/api/rpl/medicinal-products/{ID}/package

# Bulk downloads (XML)
GET https://rejestrymedyczne.ezdrowie.gov.pl/api/rpl/medicinal-products/public-pl-report/get-xml/overall
GET https://rejestrymedyczne.ezdrowie.gov.pl/api/rpl/medicinal-products/public-pl-report/get-xml/incremental
```

#### Data Fields (from bulk XML/CSV)
- Product name (Polish brand name, e.g., "Amlozek")
- Active substance (INN name, e.g., "Amlodipinum")
- ATC code
- Pharmaceutical form
- Strength/dose
- Marketing authorization holder
- Authorization number and type (NAR, MRP, DCP, CEN)
- Authorization status

#### Strengths
- **Contains ATC codes** -- enables mapping to international standards
- **Active substance in INN** -- enables mapping to RxNorm/OpenFDA
- Includes patient leaflets (PIL) in Polish
- Daily updates
- Free, no authentication needed

#### Limitations
- No formal API documentation (endpoints reverse-engineered from web UI)
- PILs are in PDF format (need OCR/parsing)
- SmPC also in PDF
- No REST search API with documented parameters

### 7.2 Mapping Polish Drug Names to International Standards

**Strategy for PostVisit.ai:**

```
Polish Brand Name (e.g., "Amlozek")
    |
    v
RPL Database (URPL) --> Active Substance: "Amlodipinum" + ATC Code: C08CA01
    |
    v
INN Name: "amlodipine"
    |
    v
RxNorm API (getDrugs?name=amlodipine) --> RxCUI
    |
    v
OpenFDA API (search=openfda.generic_name:amlodipine) --> Full drug label
    |
    v
Patient-friendly info (adverse_reactions, warnings, dosing, interactions)
```

**Practical mapping examples:**

| Polish Name | Active Substance | INN | ATC Code |
|------------|-----------------|-----|----------|
| Amlozek | Amlodipinum | amlodipine | C08CA01 |
| Polocard | Acidum acetylsalicylicum | acetylsalicylic acid (aspirin) | B01AC06 |
| Metformax | Metforminum | metformin | A10BA02 |
| Enarenal | Enalaprilum | enalapril | C09AA02 |
| Acard | Acidum acetylsalicylicum | acetylsalicylic acid | B01AC06 |

**Implementation approach:**
1. Download the RPL bulk XML/CSV from dane.gov.pl (one-time, ~daily refresh)
2. Build a local lookup table: Polish brand name -> INN -> ATC code
3. Use the INN name to query OpenFDA / RxNorm for clinical content
4. For content not available in English databases, fall back to Polish PIL PDFs

---

## 8. European Drug Coverage (EMA)

**Website:** https://www.ema.europa.eu/en/medicines/download-medicine-data

### What It Provides
EMA provides data on centrally authorized medicines in the EU (approximately 1,500+ products). These are typically newer, innovative medicines.

### Data Available (FREE)

| File | Content |
|------|---------|
| `medicines-output-medicines-report_en.xlsx` | Approved medicines, status, therapeutic area |
| Post-authorisation procedures Excel | Variations and updates |
| Article 57 database (Excel) | All authorized medicines with MAH contact info |

### Access Methods
- **Excel downloads:** Updated nightly, freely available
- **JSON data files:** Full website content available in JSON (completed Jan 2026)
  - URL: https://www.ema.europa.eu/en/about-us/about-website/download-website-data-json-data-format
  - Updated twice daily
- **ePI API (pilot):** Electronic Product Information in FHIR format
  - Pilot completed Aug 2024
  - Based on HL7 FHIR standard
  - Contains structured package leaflets

### EMA Data Fields
- Medicine name
- Active substance (INN)
- Therapeutic area
- ATC code
- Marketing authorization holder
- Authorization status and date
- EPAR (European Public Assessment Report) links

### Strengths
- Covers EU-wide centrally authorized medicines
- JSON format available for automation
- ePI in FHIR format is forward-looking
- Links to national registers of all EU member states

### Limitations
- **Only centrally authorized medicines** (not all drugs on EU markets)
- Most drugs authorized at national level (like Polish generics) are NOT in EMA's database
- ePI API is still in pilot/early stages
- No comprehensive REST API for full drug search
- EPARs are PDF documents (not structured data)

### National Registers
EMA maintains a list of national medicine registers across EU:
https://www.ema.europa.eu/en/medicines/national-registers-authorised-medicines

This includes links to the Polish RPL and equivalents in other EU countries.

---

## 9. Hackathon Recommendations

### Fastest Path to a Working Drug Lookup Demo

#### Recommended Architecture (can be built in hours)

```
User Input: Drug name (Polish or English)
    |
    v
[Step 1] Local Lookup Table (from Polish RPL CSV)
    |-- Maps Polish brand name -> INN (generic name) + ATC code
    |-- Pre-downloaded, loaded at startup
    |
    v
[Step 2] RxNorm API
    |-- Normalize the drug name: getDrugs or getApproximateMatch
    |-- Get RxCUI identifier
    |-- Handles typos via getSpellingSuggestions
    |
    v
[Step 3] OpenFDA Drug Label API
    |-- Query by generic_name or rxcui
    |-- Extract: adverse_reactions, warnings, drug_interactions,
    |   dosage_and_administration, indications_and_usage,
    |   information_for_patients
    |
    v
[Step 4] LLM Processing (GPT-4 / Claude)
    |-- Summarize medical text into patient-friendly language
    |-- Generate: "What this drug does", "Common side effects",
    |   "Important warnings", "How to take it"
    |-- Translate to Polish if needed
    |
    v
Patient-Friendly Output
```

#### Priority Data Sources (in order of implementation)

| Priority | Source | What to Use It For | Setup Time |
|----------|--------|-------------------|------------|
| 1 | **OpenFDA** | Side effects, warnings, dosing, interactions, indications | 30 min |
| 2 | **RxNorm** | Drug name normalization, spelling correction, brand/generic mapping | 30 min |
| 3 | **Polish RPL** (bulk CSV) | Polish brand name -> INN mapping | 1-2 hours |
| 4 | **MedlinePlus Connect** | Patient-friendly article links | 30 min |
| 5 | **WHO ATC** (community CSV) | Drug classification/grouping | 30 min |

#### Minimal Viable Demo (2-3 hours)

For the absolute fastest path:

1. **Use OpenFDA only** -- no signup needed, instant JSON responses
2. Query: `https://api.fda.gov/drug/label.json?search=openfda.generic_name:{drug}&limit=1`
3. Extract `adverse_reactions`, `indications_and_usage`, `dosage_and_administration`, `drug_interactions`
4. Feed into LLM with prompt: "Summarize this for a patient in plain, simple language"
5. Display result

This gives you a working demo with zero dependencies beyond a single HTTP call.

#### Polish Drug Support (add 1-2 hours)

1. Download RPL data from dane.gov.pl (CSV/XML)
2. Parse into a simple dictionary: `{polish_name: {inn: "...", atc: "..."}}`
3. When user types "Amlozek", look up INN "amlodipine"
4. Query OpenFDA with the INN name
5. Optionally translate output to Polish using LLM

#### Drug Interaction Checking (add 1-2 hours)

Since the NIH Drug Interaction API was discontinued (Jan 2024), options are:
1. **OpenFDA `drug_interactions` field** -- parse the text from each drug's label
2. **DDinter** -- manual lookup or light scraping (302k interaction pairs)
3. **LLM-based** -- feed two drugs' interaction sections to LLM for analysis
4. **DrugBank CC0 dataset** -- if an interaction subset is in the public domain dataset

#### API Keys to Obtain (Free)

| Service | URL | Benefit |
|---------|-----|---------|
| OpenFDA | https://open.fda.gov/apis/authentication/ | 240 req/min (vs 40 without) |

No other API keys needed. RxNorm, DailyMed, MedlinePlus are all key-free.

### Data Coverage Summary

| Feature | US Drugs | Polish Drugs | EU Drugs |
|---------|----------|-------------|----------|
| Drug names & forms | OpenFDA, RxNorm | RPL (URPL) | EMA (centralized only) |
| Side effects | OpenFDA (labels + FAERS) | RPL PIL (PDF, Polish) | EMA EPAR (PDF) |
| Drug interactions | OpenFDA (label text) | Not available (use OpenFDA via INN) | Not available |
| Patient-friendly text | MedlinePlus, OpenFDA | RPL PIL (Polish, PDF) | EMA ePI (pilot) |
| ATC classification | RxNorm, DrugBank | RPL includes ATC | EMA includes ATC |
| Brand -> Generic mapping | RxNorm | RPL | EMA |

### Key Insight

**For Polish drugs, the most practical approach is:**
1. Use the Polish RPL to map brand name -> INN (international generic name)
2. Use the INN to query US databases (OpenFDA, RxNorm) which have the richest structured clinical content
3. Use LLM to translate and simplify the English clinical content into patient-friendly Polish

This works because the active substances (INN names) are internationally standardized -- amlodipine is amlodipine worldwide, whether the brand is "Amlozek" (Poland), "Norvasc" (US), or "Istin" (UK).

---

## Appendix: Quick Reference URLs

| Resource | URL |
|----------|-----|
| RxNorm API | https://lhncbc.nlm.nih.gov/RxNav/APIs/RxNormAPIs.html |
| OpenFDA Drug Label API | https://open.fda.gov/apis/drug/label/ |
| OpenFDA API Key Signup | https://open.fda.gov/apis/authentication/ |
| OpenFDA Searchable Fields | https://open.fda.gov/apis/drug/label/searchable-fields/ |
| DailyMed API | https://dailymed.nlm.nih.gov/dailymed/app-support-web-services.cfm |
| DrugBank (free search) | https://go.drugbank.com/ |
| WHO ATC Index | https://atcddd.fhi.no/atc_ddd_index/ |
| ATC CSV (GitHub) | https://github.com/fabkury/atcd |
| MedlinePlus Connect | https://medlineplus.gov/medlineplus-connect/web-service/ |
| MedlinePlus Drug Info | https://medlineplus.gov/druginformation.html |
| PubChem API | https://pubchem.ncbi.nlm.nih.gov/docs/pug-rest |
| DDinter 2.0 | https://ddinter2.scbdd.com/ |
| Polish RPL Search | https://rejestry.ezdrowie.gov.pl/rpl/search/public |
| Polish RPL Open Data | https://dane.gov.pl/pl/dataset/397,rejestr-produktow-leczniczych |
| Polish RPL API (bulk XML) | https://rejestrymedyczne.ezdrowie.gov.pl/api/rpl/medicinal-products/public-pl-report/get-xml/overall |
| EMA Medicine Data Download | https://www.ema.europa.eu/en/medicines/download-medicine-data |
| EMA JSON Data | https://www.ema.europa.eu/en/about-us/about-website/download-website-data-json-data-format |
| EMA National Registers | https://www.ema.europa.eu/en/medicines/national-registers-authorised-medicines |
