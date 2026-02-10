# Features Required for Demo Video

> Wyciągnięte ze scenariusza video-script-v4.docx. Każdy feature musi DZIAŁAĆ na nagraniu.
> Status: draft — scenariusz nie jest jeszcze ostateczny.

## Screen recordings do przygotowania

Scenariusz wymaga nagrań ekranu w scenach 3, 4 i 5. To definiuje scope MVP.

### Scena 3 — Patient App (phone)

| # | Feature | Opis | Priorytet |
|---|---------|------|-----------|
| 3A | Notification | "PostVisit.AI: Your visit summary is ready" — push/in-app | Must |
| 3B | Visit summary | Diagnosis, medications, next steps — plain language | Must |
| 3C | Tap-to-explain | Klik na "Paroxysmal Ventricular Contractions" → chat opens | Must |
| 3D | Q&A: "What causes this?" | AI odpowiada: stress, caffeine, lack of sleep | Must |
| 3E | Q&A: "What is propranolol?" | AI odpowiada: beta-blocker, side effects (fatigue, cold hands, dizziness), "do not stop abruptly" | Must |

### Scena 4 — Product Deep Dive (screen recording + PiP)

| # | Feature | Opis | Priorytet |
|---|---------|------|-----------|
| 4A | Wearable integration | Apple Watch data synced — heart rhythm, PVC detection | Should |
| 4B | Lab results upload | Blood work — cholesterol, potassium, thyroid + relevance notes | Should |
| 4C | Follow-up plan / timeline | BP check 2 weeks, echo 3 months — visual timeline | Should |

### Scena 5 — Doctor Dashboard

| # | Feature | Opis | Priorytet |
|---|---------|------|-----------|
| 5A | Doctor dashboard | Patient insights, flags, alerts — overview | Must |
| 5B | Patient questions feed | Co pacjent pytał, jakie odpowiedzi dostał | Must |
| 5C | 2AM alert example | System rozpoznaje pattern, cross-ref z lekami i labami, alert do lekarza | Should |

## Podsumowanie priorytetów

### Must have (bez tego nie ma demo)
1. Visit summary screen (diagnosis + meds + next steps)
2. Tap-to-explain (click term → chat)
3. Q&A chat z AI (min. 2 pytania: cause + medication)
4. Doctor dashboard (patient insights + questions feed)
5. Notification (może być mockup)

### Should have (wzmacnia demo, ale film działa bez tego)
6. Wearable integration (Apple Watch)
7. Lab results upload + analysis
8. Follow-up timeline
9. 2AM alert scenario

## Ambient scribing (implied, nie nagrywany)

Scenariusz zakłada że telefon nagrywał wizytę (Scene 3: "my phone was on the table during the visit"). To nie wymaga screen recording — wystarczy:
- 2-sekundowy flashback: telefon na biurku, ikona nagrywania
- Overlay: "* With mutual consent of doctor and patient"

Ale backend MUSI mieć endpoint który przyjmuje transkrypt i generuje visit summary.

## Dane demo potrzebne

| Dane | Źródło | Format |
|------|--------|--------|
| Transkrypt wizyty (kardiolog + pacjent) | Napisany przez Nedo (lekarza) | Text |
| Wypis lekarski / visit summary | Napisany przez Nedo | Text |
| Scenariusz: PVCs, propranolol 40mg 2x/day | seed.md | — |
| Apple Watch mock data (HR, PVC events) | Mock / generated | JSON |
| Lab results mock (cholesterol, K+, TSH) | Mock / generated | JSON |
| Doctor dashboard mock (patient list, alerts) | Mock / generated | JSON |
