# Connectors & Health Data Integration — research

> Research z 2026-02-10. Kontekst: jak PostVisit.ai może się integrować z istniejącymi rejestrami zdrowotnymi.

## Kluczowy wniosek: FHIR to wspólny język

Niezależnie od platformy (Apple, Google, szpitalne EHR) — standard który łączy wszystko to **FHIR R4** (Fast Healthcare Interoperability Resources). REST API + JSON, naturalnie pasuje do Laravel. Jeśli PostVisit.ai mówi po FHIR, może rozmawiać z praktycznie każdym systemem medycznym.

## Apple Health

### HealthKit (fitness: kroki, puls, sen)
- Dostępny **TYLKO** z natywnej aplikacji iOS (Swift)
- Zero szans na dostęp z web app
- Alternatywy: bridging services (Terra API, OneTwentyOne) — REST API do HealthKit przez natywną pośredniczkę

### Health Records (dane kliniczne)
- Używa **FHIR R4** — dostępny przez **SMART on FHIR OAuth**
- Web app MOŻE uzyskać dostęp do danych klinicznych przez FHIR
- 7 kategorii danych: medications (RxNorm), lab results (LOINC), immunizations (CVX), conditions (SNOMED), allergies, procedures (CPT/SNOMED), vitals (LOINC)
- Plus: clinical notes, COVID results

### Wymagania do integracji
- Implementacja SMART on FHIR OAuth (patient username/password + access tokens ≥10min + refresh tokens ≥3msc)
- FHIR R4 API endpoint
- Dla demo: **mock FHIR data wystarczy** — podłączenie do prawdziwego szpitala wymaga tygodni

## Google Health

### Health Connect (Android)
- Lokalna baza na urządzeniu, bez web API
- Odpowiednik HealthKit na Androidzie — wymaga natywnej apki

### Google Cloud Healthcare API
- Pełny **FHIR store** z HIPAA compliance
- Enterprise-level, drogi, ale production-ready
- Obsługuje FHIR R4, HL7v2, DICOM (obrazowanie)

### Google Fit API
- **Deprecated** — znika w 2026, nie budować na tym

## Anthropic — Claude for Healthcare (styczeń 2026)

### Co uruchomili
- **Claude for Healthcare** — dedykowany zestaw narzędzi dla healthcare
- **BAA dostępne** na Enterprise i first-party API → oficjalne wsparcie HIPAA compliance
- **Zero-training policy** na danych medycznych — dane pacjentów nie trenują modeli
- **HealthEx** — pierwsza integracja z consumer health records
- **Apple Health + Android Health Connect** — planowana integracja z aplikacjami Claude na iOS/Android

### Connectors do źródeł medycznych
- CMS (Centers for Medicare & Medicaid Services)
- ICD-10 (kody diagnostyczne)
- NPI Registry (rejestr lekarzy)
- PubMed (evidence-based medicine)

### Agentic workflows — human-in-the-loop
Trzy poziomy autonomii:
1. **Read-Only** — AI czyta, nie działa
2. **Drafting** — AI przygotowuje, człowiek zatwierdza
3. **Action with Approval** — AI działa po zatwierdzeniu

Pełny audit trail na każdym poziomie. **To jest dokładnie model doctor-in-the-loop z PostVisit.ai.**

### Kto już używa Claude w healthcare
- Banner Health (55K pracowników)
- Elation Health (EHR)
- Carta Healthcare (99% accuracy w clinical data)

## Co to oznacza dla PostVisit.ai

### Na demo (hackathon)
- Mock FHIR data — realistyczny scenariusz kardiologiczny
- Architektura pokazuje gotowość na prawdziwe integracje
- Diagram: FHIR jako standard wymiany danych

### Na produkcję
- FHIR R4 jako natywny format danych wizyty
- SMART on FHIR OAuth do Apple Health Records
- Google Cloud Healthcare API jako opcjonalny FHIR store
- BAA z Anthropic (dostępne na Enterprise)

### Argument na hackathon
PostVisit.ai wpisuje się w ekosystem, który Anthropic **aktywnie buduje**. Projekt nie jest fantasy — jest dokładnie w kierunku, który Anthropic obrał miesiąc temu. Wytyczne kliniczne + 1M context window + doctor-in-the-loop = to co Anthropic promuje jako przyszłość healthcare AI.

## Biblioteki FHIR (open source)

| Język | Biblioteka | Uwagi |
|-------|-----------|-------|
| PHP | php-fhir (dcarbone/php-fhir) | Generuje klasy PHP z FHIR definitions |
| JavaScript | fhir-client.js | Browser/Node.js, SMART on FHIR support |
| Java | HAPI FHIR | Pełny serwer/klient, gold standard |
| Python | SMART on FHIR client | Klient z OAuth |
| Cloud | Google Cloud Healthcare API | Managed FHIR store |

Dla Laravel: **php-fhir** + własny FHIR service layer w `app/Services/Fhir/`.
