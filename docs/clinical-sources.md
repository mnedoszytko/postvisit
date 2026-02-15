# Clinical Data Sources — research

> Research from 2026-02-10. Context: where to source clinical guidelines and medical data for AI context in PostVisit.ai.

## Key takeaway: most sources are open access

Good news — the most important clinical guidelines and medical knowledge bases are available for free with licenses that permit use. The biggest challenge is not licensing, but data format and volume.

## 1. Clinical guidelines — open access

### ESC (European Society of Cardiology)
- **Status:** Open access (CC-BY via Oxford Academic / European Heart Journal)
- **Available:** escardio.org/Guidelines — PDF download
- **2025 guidelines:** Valvular Heart Disease, CVD & Pregnancy, Mental Health & CVD
- **For demo (cardiology):** ideal — ESC cardiology guidelines are directly relevant

### AHA (American Heart Association)
- **Status:** Open access (Circulation, JACC journals)
- **2025 guidelines:** Acute Coronary Syndromes, CPR, High Blood Pressure Management
- **Format:** PDF + web summaries at professional.heart.org

### NICE (UK)
- **Status:** Open access, **CC-BY-NC-ND 4.0** or CC-BY 4.0
- **Note:** CC-BY-NC = non-commercial use only — important for future business model
- **Access:** nice.org.uk/guidance/published
- **2025-2026 updates:** breast cancer, heart failure, bipolar, pneumonia

### WHO
- **Status:** Fully open, **CC-BY 3.0 IGO** — most permissive
- **Commercial use:** allowed
- **Access:** who.int — PDF download

### License summary

| Source | License | Commercial use? | Redistribution? |
|--------|----------|-----------------|-----------------|
| ESC | CC-BY (journal) | yes | yes, with attribution |
| AHA | CC-BY (journal) | yes | yes, with attribution |
| NICE | CC-BY-NC or CC-BY-ND | no (BY-NC) | yes, with attribution |
| WHO | CC-BY 3.0 IGO | yes | yes, with attribution |

## 2. Medical knowledge bases and ontologies

### Open (free, production-ready)

| Database | Contents | License | Format | API? |
|------|-----------|----------|--------|------|
| **ICD-11** | Diagnostic codes (successor to ICD-10) | CC-BY-ND 3.0 IGO (free) | XML, JSON | ICD API |
| **RxNorm** | Drugs: names, dosages, forms | Public domain (NLM) | REST API + download | RxNav API |
| **LOINC** | Lab test codes | CC-BY (free) | API + tables | yes |
| **MeSH** | Medical Subject Headings (PubMed indexing) | Public domain | XML, RDF | yes |
| **DailyMed** | Official FDA drug labels | Public domain | XML, JSON, PDF | yes |
| **OpenFDA** | Drugs, adverse events, recalls | Public domain | REST API (JSON) | yes |
| **SNOMED CT** | Clinical terminology | Free in USA + IHTSDO member countries | Download from NLM | yes |

### UMLS Metathesaurus
- Integrates SNOMED CT + RxNorm + LOINC + MeSH + more
- Free license from NLM (no fees)
- **For PostVisit.ai:** single source for mapping medical terminology

## 3. PubMed and medical literature

| Resource | Access | Format | For PostVisit.ai |
|-------|--------|--------|-----------------|
| **PubMed API** (E-utilities) | Free REST API | XML/JSON | Searching articles and guidelines |
| **PubMed Central** | 2M+ full-text articles | FTP, API | Open-access subset with CC-BY |
| **ClinicalTrials.gov API** | Free REST API v2.0 (OpenAPI 3.0) | JSON | Latest clinical trials |
| **Cochrane** | Subscription (free summaries) | Web | Systematic reviews — gold standard |

## 4. Clinical Decision Support (open standards)

### CDS Hooks
- HL7 standard, open source
- EHR triggers event -> external CDS service responds with "cards" (suggestions, links)
- **For PostVisit.ai:** we could act as a CDS Hook service — EHR sends visit context, we return explanations

### OpenCDS
- Apache 2.0 license
- Framework for building standards-based CDS
- Implements CDS Hooks interface

## 5. Context window — how much can we fit?

### Typical guideline sizes

| Guideline | Pages | Estimated tokens |
|----------|--------|-----------------|
| ESC Cardiology guideline (full) | 100-150 pages | ~65,000-100,000 tokens |
| NICE Blood Pressure guideline | ~60 pages | ~40,000 tokens |
| AHA CPR guideline | ~80 pages | ~50,000 tokens |
| Multiple guidelines combined | | ~200,000-300,000 tokens |

### 1M context window can hold:
- **4-8 full guidelines** simultaneously
- Plus patient data (~5-10K tokens)
- Plus conversation history (~5-20K tokens)
- Plus system prompt and guardrails (~5K tokens)
- **Leaves ~600-700K tokens of headroom**

### Anthropic Prompt Caching — game changer
- Guidelines loaded once -> cached on Anthropic's servers
- Subsequent requests: **90% token cost reduction** (cache hit)
- Cache read tokens don't count toward ITPM limit
- **Latency:** up to 85% reduction for long prompts

**Cost economics example:**
- First request (guidelines + system prompt): 160K tokens = ~$2.40
- Cached follow-up (patient query): 10K tokens = ~$0.15
- In a clinical setting: cache hit rate is very high (same patient, same guidelines)

## 6. Licensing question: can PDFs be included in an open source repo?

### Can be included in repo:
- **WHO guidelines** (CC-BY 3.0 IGO) — yes, with attribution
- **ESC guidelines** (CC-BY from journal) — yes, with attribution
- **AHA guidelines** (CC-BY from journal) — yes, with attribution
- **RxNorm, OpenFDA, DailyMed data** — yes, public domain

### Should NOT be included in repo:
- **NICE** (CC-BY-NC) — not for commercial repo
- **UpToDate, DynaMed, BMJ Best Practice** — subscription, copyright
- **Institutional guidelines** — proprietary

### Recommendation:
For demo: include 1 ESC guideline (CC-BY) as a text file in `demo/guidelines/`. License-safe.

For production: guidelines fetched dynamically (API/agent), not bundled in repo.

## 7. Alternative: AI agent searches for evidence online

If we don't want to or can't bundle PDFs — we build an agent that searches for evidence in real time.

### Available APIs for live search

#### Tier 1 — free, no registration required
| API | What it provides | Rate limit | Full text? |
|-----|---------|-----------|------------|
| **PubMed E-utilities** | 38M+ articles, abstracts | 10 req/s (with API key) | Abstracts; full text via PMC |
| **Europe PMC** | 50M+ articles, including NHS guidelines | Reasonable use | open access articles |
| **ClinicalTrials.gov v2.0** | 500K+ trials | ~50 req/min | Structured data |
| **OpenAlex** | 474M+ publications | 100K credits/day | Abstracts + DOI links |
| **NIH Clinical Table Search** | ICD-10, RxTerms, NPI codes | Unlimited | Structured lookup |

#### Tier 2 — free, registration required
| API | What it provides | Notes |
|-----|---------|-------|
| **Semantic Scholar** | Semantic search, citation graphs | 100 req/s with API key |
| **OpenEvidence** | Evidence-based answers (AI-powered) | NPI verification required |
| **DrugBank** | 500K+ drugs, 1.4M drug-drug interactions | Free discovery tier |

#### Tier 3 — require agreement/subscription
| API | What it provides | Notes |
|-----|---------|-------|
| **NICE Syndication API** | UK guidelines structured | License agreement |
| **TRIP Database** | Clinical evidence search | Institutional request |
| **Cochrane** | Systematic reviews | Contact Wiley |

### Agent flow: searching for evidence in visit context

```
[Patient question] + [Visit context]
           │
           ▼
[Claude Opus 4.6 — query planning]
  "Patient asks about propranolol-caffeine interaction"
  → generates search queries:
    1. PubMed: "propranolol caffeine interaction"
    2. OpenFDA: drug interactions for propranolol
    3. DrugBank: propranolol interactions
           │
           ▼
[Evidence Search Agent]
  → parallel API calls
  → collects results
  → ranks by relevance
           │
           ▼
[Claude Opus 4.6 — synthesis]
  Context: visit data + search results + cached guidelines
  → evidence-based answer with citations
  → patient-friendly language
           │
           ▼
[Response to patient with sources]
```

### Key APIs to start with

**PubMed E-utilities** — the most powerful free medical API:
- `esearch.fcgi` — search articles
- `efetch.fcgi` — fetch abstracts/metadata
- `elink.fcgi` — related articles
- Free API key: register at NCBI

**Europe PMC** — better than PubMed for full-text:
- Includes everything from PubMed PLUS preprints, NHS guidelines, patents
- REST API without registration
- Full text for open access articles

**OpenFDA** — drugs and safety:
- `/drug/label.json` — drug labels
- `/drug/event.json` — adverse events
- Zero auth, public domain

## 8. If guidelines are NOT available — upload functionality

Many institutions have **their own guidelines** (Mayo Clinic, Cleveland Clinic, local hospitals). For production, an upload feature is needed.

### Processing pipeline

```
Upload (PDF/DOCX)
  ↓
Extraction (text, structure, tables)
  ↓
Chunking (5-10K token sections with metadata)
  ↓
Vectorization (embedding with clinical model)
  ↓
Indexing (vector DB: Qdrant/Weaviate/Pinecone)
  ↓
Cache management (Anthropic prompt caching)
  ↓
Retrieval (semantic search + reranking)
  ↓
Injection into Claude context
```

### Security requirements for uploads
- Encryption at rest (AES-256)
- Role-based access (doctor sees their institution's guidelines only)
- Audit log (who uploaded, who accessed)
- Secure purge policy
- BAA with institution

## 9. Knowledge sources architecture (updated)

```
┌─────────────────────────────────────────────────────────────┐
│                    KNOWLEDGE SOURCES                         │
├─────────────┬──────────────┬────────────────┬───────────────┤
│ Bundled     │  Live APIs   │ Evidence       │ Organization  │
│ (in repo)   │  (real-time) │ Search Agent   │ Uploads       │
│             │              │                │               │
│ • 1 ESC     │ • OpenFDA    │ • PubMed       │ • Custom PDFs │
│   guideline │ • RxNorm     │ • Europe PMC   │ • Local       │
│   (CC-BY)   │ • ICD-11     │ • OpenAlex     │   protocols   │
│             │ • DailyMed   │ • Semantic Sch. │ • Institut.  │
│             │ • DrugBank   │ • ClinTrials   │   pathways    │
└──────┬──────┴──────┬───────┴───────┬────────┴──────┬────────┘
       │             │               │               │
       ▼             ▼               ▼               ▼
┌─────────────────────────────────────────────────────────────┐
│              Context Assembly Layer                          │
│  (selection, chunking, caching, citation tracking)           │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│  Anthropic Prompt Cache (90% token savings)                  │
│  → Claude Opus 4.6 (1M context window)                       │
└─────────────────────────────────────────────────────────────┘
```

## 10. What to use for demo (hackathon)

**Minimum:**
- 1 ESC cardiology guideline (CC-BY, text in `demo/guidelines/`)
- RxNorm API for drug verification (propranolol in the demo scenario)
- Mock patient data from seed.md

**Extension (if time permits):**
- Evidence Search Agent (PubMed + OpenFDA) — live search for patient questions
- ICD-11 API — diagnostic codes
- Prompt caching for guidelines
- DrugBank — drug-drug interactions

**Not for demo:**
- Upload functionality (too much work)
- Vector DB / RAG (overkill for hackathon — 1M context window is sufficient)
- Cochrane systematic reviews
- NICE API (requires license agreement)
