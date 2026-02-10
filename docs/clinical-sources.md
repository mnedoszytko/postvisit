# Clinical Data Sources — research

> Research z 2026-02-10. Kontekst: skąd wziąć wytyczne kliniczne i dane medyczne do kontekstu AI w PostVisit.ai.

## Kluczowy wniosek: większość jest open access

Dobre wiadomości — najważniejsze wytyczne kliniczne i bazy wiedzy medycznej są dostępne za darmo z licencjami pozwalającymi na użycie. Największym wyzwaniem jest nie licencja, ale format i objętość danych.

## 1. Wytyczne kliniczne — open access

### ESC (European Society of Cardiology)
- **Status:** Open access (CC-BY przez Oxford Academic / European Heart Journal)
- **Dostępne:** escardio.org/Guidelines — PDF do pobrania
- **2025 guidelines:** Valvular Heart Disease, CVD & Pregnancy, Mental Health & CVD
- **Dla demo (kardiologia):** ✅ idealne — ESC cardiology guidelines są bezpośrednio relevantne

### AHA (American Heart Association)
- **Status:** Open access (Circulation, JACC journals)
- **2025 guidelines:** Acute Coronary Syndromes, CPR, High Blood Pressure Management
- **Format:** PDF + web summaries na professional.heart.org

### NICE (UK)
- **Status:** Open access, **CC-BY-NC-ND 4.0** lub CC-BY 4.0
- **Uwaga:** CC-BY-NC = non-commercial use only — ważne dla przyszłego modelu biznesowego
- **Dostęp:** nice.org.uk/guidance/published
- **2025-2026 updates:** breast cancer, heart failure, bipolar, pneumonia

### WHO
- **Status:** Fully open, **CC-BY 3.0 IGO** — najbardziej permissive
- **Commercial use:** ✅ dozwolony
- **Dostęp:** who.int — PDF download

### Podsumowanie licencji

| Źródło | Licencja | Commercial use? | Redistribution? |
|--------|----------|-----------------|-----------------|
| ESC | CC-BY (journal) | ✅ | ✅ z atrybucją |
| AHA | CC-BY (journal) | ✅ | ✅ z atrybucją |
| NICE | CC-BY-NC lub CC-BY-ND | ❌ (BY-NC) | ✅ z atrybucją |
| WHO | CC-BY 3.0 IGO | ✅ | ✅ z atrybucją |

## 2. Bazy wiedzy medycznej i ontologie

### Otwarte (free, production-ready)

| Baza | Co zawiera | Licencja | Format | API? |
|------|-----------|----------|--------|------|
| **ICD-11** | Kody diagnostyczne (następca ICD-10) | CC-BY-ND 3.0 IGO (free) | XML, JSON | ✅ ICD API |
| **RxNorm** | Leki: nazwy, dawki, formy | Public domain (NLM) | REST API + download | ✅ RxNav API |
| **LOINC** | Kody badań laboratoryjnych | CC-BY (free) | API + tabele | ✅ |
| **MeSH** | Medical Subject Headings (indeksowanie PubMed) | Public domain | XML, RDF | ✅ |
| **DailyMed** | Oficjalne etykiety leków FDA | Public domain | XML, JSON, PDF | ✅ |
| **OpenFDA** | Leki, adverse events, recalls | Public domain | REST API (JSON) | ✅ |
| **SNOMED CT** | Terminologia kliniczna | Free w USA + krajach IHTSDO | Download z NLM | ✅ |

### UMLS Metathesaurus
- Integruje SNOMED CT + RxNorm + LOINC + MeSH + więcej
- Free license z NLM (bez opłat)
- **Dla PostVisit.ai:** jedno źródło do mapowania terminologii medycznej

## 3. PubMed i literatura medyczna

| Zasób | Dostęp | Format | Dla PostVisit.ai |
|-------|--------|--------|-----------------|
| **PubMed API** (E-utilities) | Free REST API | XML/JSON | Wyszukiwanie artykułów i guidelines |
| **PubMed Central** | 2M+ artykułów full-text | FTP, API | Open-access subset z CC-BY |
| **ClinicalTrials.gov API** | Free REST API v2.0 (OpenAPI 3.0) | JSON | Najnowsze badania kliniczne |
| **Cochrane** | Subskrypcja (darmowe summaries) | Web | Systematic reviews — gold standard |

## 4. Clinical Decision Support (otwarte standardy)

### CDS Hooks
- Standard HL7, open source
- EHR triggeruje event → zewnętrzny CDS service odpowiada "cards" (sugestie, linki)
- **Dla PostVisit.ai:** możemy być CDS Hook service — EHR wysyła kontekst wizyty, my zwracamy wyjaśnienia

### OpenCDS
- Apache 2.0 license
- Framework do budowania standards-based CDS
- Implementuje CDS Hooks interface

## 5. Context window — ile zmieścimy?

### Rozmiar typowych guidelines

| Wytyczna | Strony | Szacunek tokenów |
|----------|--------|-----------------|
| ESC Cardiology guideline (pełna) | 100-150 stron | ~65,000-100,000 tokens |
| NICE Blood Pressure guideline | ~60 stron | ~40,000 tokens |
| AHA CPR guideline | ~80 stron | ~50,000 tokens |
| Kilka guidelines razem | | ~200,000-300,000 tokens |

### 1M context window pomieści:
- **4-8 pełnych guidelines** jednocześnie
- Plus dane pacjenta (~5-10K tokens)
- Plus historia konwersacji (~5-20K tokens)
- Plus system prompt i guardrails (~5K tokens)
- **Zostaje ~600-700K tokens zapasu**

### Anthropic Prompt Caching — game changer
- Guidelines ładowane raz → cache'owane na serwerze Anthropic
- Kolejne requesty: **90% zniżka na tokeny** (cache hit)
- Cache read tokens nie liczą się do limitu ITPM
- **Latencja:** do 85% redukcji dla długich promptów

**Przykład ekonomii:**
- Pierwszy request (guidelines + system prompt): 160K tokens = ~$2.40
- Cached follow-up (patient query): 10K tokens = ~$0.15
- W clinical setting: cache hit rate bardzo wysoki (ten sam pacjent, te same wytyczne)

## 6. Kwestia licencji: czy PDFy mogą być w open source repo?

### Można wrzucić do repo:
- **WHO guidelines** (CC-BY 3.0 IGO) — ✅ z atrybucją
- **ESC guidelines** (CC-BY z journala) — ✅ z atrybucją
- **AHA guidelines** (CC-BY z journala) — ✅ z atrybucją
- **RxNorm, OpenFDA, DailyMed data** — ✅ public domain

### NIE wrzucać do repo:
- **NICE** (CC-BY-NC) — nie do commercial repo
- **UpToDate, DynaMed, BMJ Best Practice** — subscription, copyright
- **Institutional guidelines** — proprietary

### Rekomendacja:
Na demo: wrzuć 1 ESC guideline (CC-BY) jako text file w `demo/guidelines/`. Bezpieczne licencyjnie.

Na produkcję: guidelines pobierane dynamicznie (API/agent), nie bundlowane w repo.

## 7. Alternatywa: AI agent szuka evidence online

Jeśli nie chcemy/nie możemy bundlować PDFów — budujemy agenta, który szuka evidence na żywo.

### Dostępne API do live search

#### Tier 1 — darmowe, bez rejestracji
| API | Co daje | Rate limit | Full text? |
|-----|---------|-----------|------------|
| **PubMed E-utilities** | 38M+ artykułów, abstracts | 10 req/s (z API key) | Abstracts; full text przez PMC |
| **Europe PMC** | 50M+ artykułów, w tym NHS guidelines | Reasonable use | ✅ open access articles |
| **ClinicalTrials.gov v2.0** | 500K+ trials | ~50 req/min | Structured data |
| **OpenAlex** | 474M+ publikacji | 100K credits/day | Abstracts + DOI links |
| **NIH Clinical Table Search** | ICD-10, RxTerms, NPI kody | Nieograniczony | Structured lookup |

#### Tier 2 — darmowe, rejestracja wymagana
| API | Co daje | Uwagi |
|-----|---------|-------|
| **Semantic Scholar** | Semantic search, citation graphs | 100 req/s z API key |
| **OpenEvidence** | Evidence-based answers (AI-powered) | NPI verification wymagane |
| **DrugBank** | 500K+ drugs, 1.4M drug-drug interactions | Free discovery tier |

#### Tier 3 — wymagają umowy/subskrypcji
| API | Co daje | Uwagi |
|-----|---------|-------|
| **NICE Syndication API** | UK guidelines structured | License agreement |
| **TRIP Database** | Clinical evidence search | Institutional request |
| **Cochrane** | Systematic reviews | Contact Wiley |

### Agent flow: szukanie evidence w kontekście wizyty

```
[Patient question] + [Visit context]
           │
           ▼
[Claude Opus 4.6 — query planning]
  "Pacjent pyta o interakcje propranololu z kofeiną"
  → generuje search queries:
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

### Kluczowe API na start

**PubMed E-utilities** — najpotężniejsze darmowe API medyczne:
- `esearch.fcgi` — szukaj artykułów
- `efetch.fcgi` — pobieraj abstracts/metadata
- `elink.fcgi` — powiązane artykuły
- Free API key: rejestracja na NCBI

**Europe PMC** — lepsze niż PubMed dla full-text:
- Zawiera wszystko z PubMed PLUS preprints, NHS guidelines, patenty
- REST API bez rejestracji
- Full text dla open access articles

**OpenFDA** — leki i bezpieczeństwo:
- `/drug/label.json` — etykiety leków
- `/drug/event.json` — adverse events
- Zero auth, public domain

## 8. Jeśli guidelines NIE są dostępne — upload functionality

Wiele instytucji ma **własne wytyczne** (Mayo Clinic, Cleveland Clinic, lokalne szpitale). Na produkcję potrzebna jest funkcja uploadu.

### Pipeline przetwarzania

```
Upload (PDF/DOCX)
  ↓
Extraction (text, struktura, tabele)
  ↓
Chunking (5-10K token sekcje z metadanymi)
  ↓
Vectorization (embedding klinicznym modelem)
  ↓
Indexing (vector DB: Qdrant/Weaviate/Pinecone)
  ↓
Cache management (Anthropic prompt caching)
  ↓
Retrieval (semantic search + reranking)
  ↓
Injection do kontekstu Claude
```

### Wymogi bezpieczeństwa dla uploadów
- Encryption at rest (AES-256)
- Role-based access (lekarz widzi guidelines swojej instytucji)
- Audit log (kto uploadował, kto czytał)
- Secure purge policy
- BAA z instytucją

## 9. Architektura źródeł wiedzy (zaktualizowana)

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

## 10. Co użyć na demo (hackathon)

**Minimum:**
- 1 ESC cardiology guideline (CC-BY, text w `demo/guidelines/`)
- RxNorm API do weryfikacji leków (propranolol w scenariuszu demo)
- Mock patient data z seed.md

**Rozszerzenie (jeśli czas pozwoli):**
- Evidence Search Agent (PubMed + OpenFDA) — live search na pytanie pacjenta
- ICD-11 API — kody diagnostyczne
- Prompt caching dla guidelines
- DrugBank — drug-drug interactions

**Nie na demo:**
- Upload functionality (zbyt dużo pracy)
- Vector DB / RAG (overkill na hackathon — 1M context window wystarczy)
- Cochrane systematic reviews
- NICE API (wymaga license agreement)
