# STT & Ambient Scribing — research

> Research z 2026-02-10. Kontekst: provider-agnostic speech-to-text dla PostVisit.ai (iOS mobile app).

## Kluczowy wniosek: prosto, wymiennie, nie zamykamy się

Aplikacja jest mobilna (iOS). Budujemy abstraction layer (Adapter pattern) — provider wymienialny z `.env`. Nie komplikujemy.

## Providerzy — trzy opcje + fallback

### 1. Whisper (primary — open source)
- **OpenAI Whisper** — darmowy, offline, self-hosted
- Accuracy: 90-95% (ogólna), medical terms wymagają custom vocabulary
- Brak diaryzacji (trzeba oddzielnie: Pyannote)
- **whisper.cpp** — 70x szybszy, C++, działa na urządzeniu
- **faster-whisper** — CTranslate2, szybszy inference na serwerze
- **WhisperX** — Whisper + Pyannote + word timestamps w jednym (najlepsza opcja self-hosted)
- HIPAA: ✅ dane nie opuszczają infry

### 2. Google Cloud STT (cloud option)
- Model `medical_conversation` + `medical_dictation`
- 120 języków
- Medical term accuracy: ~50% (słabsza niż dedykowane)
- Cena: $0.006-0.009/s
- HIPAA: ✅ z BAA

### 3. iOS native Speech Recognition (on-device)
- Apple Speech Framework — działa offline na iOS 17+
- Zero kosztów, zero latencji
- Accuracy: dobra dla ogólnej mowy, słabsza na terminologii medycznej
- Brak diaryzacji
- Najlepsza opcja na "quick capture" z telefonu pacjenta
- Dane nie opuszczają urządzenia

### Fallback: dedykowane medyczne STT
Gdyby accuracy ogólnych modeli nie wystarczała:

| Provider | WER (medical) | Cena | Diaryzacja | HIPAA |
|----------|--------------|------|------------|-------|
| Deepgram Nova-3 Medical | 3.45% | $0.0077/min | ✅ built-in | ✅ BAA |
| AssemblyAI Slam-1 | 66% mniej missed entities | Enterprise | ✅ built-in | ✅ BAA |
| AWS Transcribe Medical | 57.9% term recognition | $1.44/h | ✅ | ✅ |

To jest backup — nie primary. Nie komplikujemy stacku na start.

## Architektura abstraction layer

```
┌─────────────────────────────────────────┐
│     Laravel Backend                      │
│     (uses SpeechToTextProvider interface) │
└────────────┬────────────────────────────┘
             │
┌────────────▼────────────────────────────┐
│   SpeechToTextProvider (interface)        │
│   - transcribe(audio): Transcription     │
│   - transcribeStream(stream): AsyncIter  │
│   - getSupportedFormats(): array         │
└────────────┬────────────────────────────┘
             │
    ┌────────┼──────────┬──────────┐
    │        │          │          │
┌───▼────┐ ┌▼───────┐ ┌▼────────┐ ┌──────────┐
│Whisper │ │Google  │ │iOS      │ │Medical   │
│(self-  │ │Cloud   │ │Native   │ │fallback  │
│hosted) │ │STT     │ │(client) │ │(Deepgram)│
└────────┘ └────────┘ └─────────┘ └──────────┘
```

W `.env`:
```
STT_PROVIDER=whisper
STT_FALLBACK=google
```

```php
// app/Providers/SttServiceProvider.php
$this->app->bind(SpeechToTextProvider::class, function () {
    return match (config('services.stt.provider')) {
        'whisper'   => new WhisperProvider(),
        'google'    => new GoogleSttProvider(),
        'deepgram'  => new DeepgramProvider(),
    };
});
```

**iOS native** jest osobnym case'em — transkrypcja dzieje się na urządzeniu, backend dostaje gotowy tekst. Nie przechodzi przez abstraction layer na serwerze.

## Audio flow (iOS app)

```
[iOS App] ──record──▶ [Audio Buffer]
                           │
              ┌────────────┼────────────┐
              │            │            │
         iOS Native    Send to      Send to
         (on-device)   Backend      Backend
              │        (real-time)  (batch)
              ▼            ▼            ▼
         Text result   WebSocket    POST /api/transcribe
              │        + Reverb     + FormData (audio blob)
              │            │            │
              └────────────┴────────────┘
                           │
                           ▼
                    [Transcription result]
                           │
                           ▼
                    [Claude Opus 4.6]
                    (entity extraction,
                     SOAP generation)
```

## Medical scribing features

### Speaker diarization (kto mówi?)
- WhisperX + Pyannote: doctor vs patient labeling (self-hosted)
- Google Cloud STT: built-in speaker diarization
- iOS native: brak — trzeba heurystyki lub osobny model

### Medical entity extraction (po transkrypcji)
- Claude Opus 4.6 wyciąga: objawy, diagnozy, leki, dawki, badania
- Structured output (JSON) → zapisane w Visit context

### SOAP note generation
- Transkrypt → Claude z system promptem → Subjective / Objective / Assessment / Plan
- Lekarz review'uje i zatwierdza (doctor-in-the-loop)

## Ambient scribing — best practices

1. **Zgoda pacjenta** — explicite, przed nagrywaniem
2. **Audio nie opuszcza infry** (ideał) — lub szyfrowane in transit
3. **Kasowanie surowego audio** po transkrypcji (retention policy)
4. **Audit log** — kto nagrywał, kiedy, co transkrybowano
5. **Minimalizacja** — transkrypt bez identyfikatorów jeśli nie potrzebne

## Na demo (hackathon)

Najprostsza ścieżka:
1. Nagrany audio clip (scenariusz kardiologiczny) — pre-recorded
2. Whisper (self-hosted) transkrybuje
3. Claude wyciąga entities + generuje summary
4. Wynik widoczny na ekranie pacjenta

Nie potrzebujemy real-time streaming na demo. Batch wystarczy.
