# Decyzje projektowe — log dyskusji

Ten dokument zapisuje wszystkie decyzje podjęte w trakcie projektowania PostVisit.ai.

## Data: 2026-02-10

### Decyzja 1: Struktura repozytorium — Monorepo
**Status:** Przyjęte

Jeden repo zawierający backend (Laravel), frontend (Vue), dokumentację, prompty i dane demo.

```
postvisit/
├── backend/            # Laravel API
├── frontend/           # Vue SPA
├── docs/               # dokumentacja robocza
├── prompts/            # system prompts dla Opus (wersjonowane jak kod)
├── demo/               # dane demo, scenariusze, seed data
├── CLAUDE.md
├── README.md
├── LICENSE
├── SECURITY.md
└── .env.example
```

### Decyzja 2: Dane demo — pisane przez lekarza
**Status:** Przyjęte

Użytkownik (lekarz) napisze realistyczny wypis lekarski, zalecenia i dane pacjenta. Scenariusz z seed.md (pobudzenia komorowe, propranolol) jest bazą, ale zostanie rozbudowany do pełnego, wiarygodnego dokumentu medycznego.

### Decyzja 3: Ambient scribing / transkrypcja — MUSI być w demo
**Status:** Przyjęte

Transkrypcja rozmowy lekarz-pacjent jest krytycznym elementem demo. Bez tego demo nie ma sensu. System musi pokazać jak transkrypt jest źródłem kontekstu dla AI.

### Decyzja 4: Doctor-in-the-loop — oba widoki
**Status:** Przyjęte

Demo będzie zawierać:
- **Widok pacjenta** — główny ekran z wizytą, Q&A, wyjaśnienia
- **Widok lekarza** — dashboard z feedbackiem, kontekstem

Film demo pokaże obie strony.

### Decyzja 5: Hosting — Forge + Hetzner
**Status:** Przyjęte

- Deploy via Laravel Forge
- Serwer na Hetznerze
- Claude Code ma dostęp do Hetznera przez API
- Development lokalny na MacBook Air z Herd

### Decyzja 6: Kontekst AI — wizyta + wytyczne kliniczne
**Status:** Przyjęte

Kontekst dla Opus 4.6 nie jest ograniczony do danych wizyty. Zawiera również:
- Dane z konkretnej wizyty (wypis, leki, badania, transkrypcja)
- **Wytyczne kliniczne** (np. ESC — European Society of Cardiology, AHA — American Heart Association)
- Pozwala Opus na odpowiedzi oparte o evidence-based medicine, w kontekście tego konkretnego pacjenta

To silny punkt dla hackathonu — pokazuje kreatywne użycie 1M token context window Opus 4.6.

### Decyzja 7: Pliki repo wymagane dla hackathonu
**Status:** Przyjęte

Na podstawie analizy wymagań hackathonu i najlepszych praktyk:

| Plik | Cel | Priorytet |
|------|-----|-----------|
| `README.md` | Pierwsze co widzą sędziowie — musi być doskonały | Krytyczny |
| `LICENSE` | Open source wymagane — MIT | Krytyczny |
| `.env.example` | Pokazuje profesjonalne podejście do secrets | Krytyczny |
| `CLAUDE.md` | Antropic-specific — pokazuje głęboką integrację z Claude Code | Krytyczny |
| `SECURITY.md` | Healthcare AI — bezpieczeństwo to must-have | Wysoki |
| `docs/architecture.md` | Pokazuje przemyślany design | Wysoki |
| Disclaimer w README | "Demo only, no real patient data, not for clinical use" | Krytyczny |

### Decyzja 8: Scenariusze demo video
**Status:** Oczekuje

Użytkownik wklei 2 scenariusze filmiku. Czekamy na dane.

### Decyzja 9: Hackathon — kluczowe fakty
**Status:** Informacja

- **Hackathon:** Built with Opus 4.6 (Anthropic + Cerebral Valley)
- **Daty:** 10-16 lutego 2026
- **Deadline:** Poniedziałek 16 lutego, 15:00 EST
- **Nagrody:** $100K w API credits ($50K/1st, $30K/2nd, $10K/3rd + 2x $5K special)
- **Special prizes:** "Most Creative Opus 4.6 Exploration" i "The Keep Thinking Prize"
- **Sędziowie:** 6 osób z Anthropic (Boris Cherny, Cat Wu, Thariq Shihpar, Lydia Hallie, Ado Kukic, Jason Bigman)
- **Winners showcase:** 21 lutego w SF
- **Submission:** GitHub repo + demo video (1-5 min) + opis projektu (<500 słów)
