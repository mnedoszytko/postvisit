# STT & Ambient Scribing — research

> Research from 2026-02-10. Context: provider-agnostic speech-to-text for PostVisit.ai (iOS mobile app).

## Key takeaway: keep it simple, swappable, no vendor lock-in

The app is mobile (iOS). We build an abstraction layer (Adapter pattern) — the provider is swappable via `.env`. No overcomplication.

## Providers — three options + fallback

### 1. Whisper (primary — open source)
- **OpenAI Whisper** — free, offline, self-hosted
- Accuracy: 90-95% (general), medical terms require custom vocabulary
- No diarization (requires a separate tool: Pyannote)
- **whisper.cpp** — 70x faster, C++, runs on-device
- **faster-whisper** — CTranslate2, faster inference on the server
- **WhisperX** — Whisper + Pyannote + word timestamps in one (best self-hosted option)
- HIPAA: ✅ data never leaves our infrastructure

### 2. Google Cloud STT (cloud option)
- Model `medical_conversation` + `medical_dictation`
- 120 languages
- Medical term accuracy: ~50% (weaker than dedicated solutions)
- Price: $0.006-0.009/s
- HIPAA: ✅ with BAA

### 3. iOS native Speech Recognition (on-device)
- Apple Speech Framework — works offline on iOS 17+
- Zero cost, zero latency
- Accuracy: good for general speech, weaker on medical terminology
- No diarization
- Best option for "quick capture" from the patient's phone
- Data never leaves the device

### Fallback: dedicated medical STT
If accuracy of general models proves insufficient:

| Provider | WER (medical) | Price | Diarization | HIPAA |
|----------|--------------|-------|------------|-------|
| Deepgram Nova-3 Medical | 3.45% | $0.0077/min | ✅ built-in | ✅ BAA |
| AssemblyAI Slam-1 | 66% fewer missed entities | Enterprise | ✅ built-in | ✅ BAA |
| AWS Transcribe Medical | 57.9% term recognition | $1.44/h | ✅ | ✅ |

This is a backup — not primary. We don't complicate the stack at launch.

## Abstraction layer architecture

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

In `.env`:
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

**iOS native** is a separate case — transcription happens on-device, the backend receives the finished text. It does not go through the server-side abstraction layer.

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

### Speaker diarization (who is speaking?)
- WhisperX + Pyannote: doctor vs patient labeling (self-hosted)
- Google Cloud STT: built-in speaker diarization
- iOS native: none — requires heuristics or a separate model

### Medical entity extraction (post-transcription)
- Claude Opus 4.6 extracts: symptoms, diagnoses, medications, dosages, lab orders
- Structured output (JSON) → stored in Visit context

### SOAP note generation
- Transcript → Claude with system prompt → Subjective / Objective / Assessment / Plan
- Physician reviews and approves (doctor-in-the-loop)

## Ambient scribing — best practices

1. **Patient consent** — explicit, obtained before recording
2. **Audio never leaves our infrastructure** (ideal) — or encrypted in transit
3. **Delete raw audio** after transcription (retention policy)
4. **Audit log** — who recorded, when, what was transcribed
5. **Minimization** — transcript without identifiers when not needed

## For the demo (hackathon)

Simplest path:
1. Pre-recorded audio clip (cardiology scenario) — pre-recorded
2. Whisper (self-hosted) transcribes
3. Claude extracts entities + generates summary
4. Result displayed on the patient's screen

We don't need real-time streaming for the demo. Batch is sufficient.
