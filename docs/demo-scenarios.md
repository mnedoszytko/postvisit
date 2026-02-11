# Demo Scenarios — Clinical Visit Library

PostVisit.ai demo scenarios based on sample cardiology patient cases. Each dialogue was generated from a clinical scenario template, labeled with Doctor/Patient speaker attribution, and synthesized using ElevenLabs Text-to-Dialogue TTS with distinct voices for doctor and patient.

> **Disclaimer:** All patient photographs are AI-generated (Flux 2 Realism via fal.ai) and do not depict real individuals. Clinical scenarios are based on representative cardiology cases but do not represent actual patients. All names, demographics, and medical data are entirely fictional.

## Pipeline

```
Clinical Scenario Template
    → Dialogue script (dialogue.txt)
    → ElevenLabs v3 Text-to-Dialogue (dialogue-tts.mp3)
```

## Scenarios

| # | Visit | Language | Patient | Clinical Problem | Key Elements |
|---|-------|----------|---------|-----------------|--------------|
| 01 | Coronarography Stenosis | EN | Marie, elderly female | Moderate stenosis LAD + RCA, exertional dyspnea | Coronarography scheduled, Crestor + Asaflow prescribed, LDL 75 too high with existing plaques, fibromyalgia comorbidity, family history (mother: cerebral hemorrhage, sister: aneurysm at 58) |
| 02 | Gastric Bypass Pre-Op | EN | Female, 1.65m / 90kg | Pre-operative cardiac clearance for gastric bypass | Echocardiography normal, stress test normal, no cardiac contraindication, arthritis |
| 03 | Hypertension Follow-up | FR | Male, elderly | Hypertension + cardiac arrhythmia | Side effects from Copérindo/Bisoprolone (constipation, insomnia), switched back to Biprésil 5/10, coronary scan clean, alcohol 1 glass/day, annual follow-up plan |
| 04 | Chest Pain Carotid | EN | Mrs. K., female | Chest pain post-surgery, carotid plaques | Belsar 10mg, BMI 35 (grade 2 obesity), no smoking, referral for vascular CT scan, cholesterol testing needed, prior cardiologist records missing from system |
| 05 | Arm Pain Fibromyalgia | FR | Female + family translator | Arm/stomach pain, fibromyalgia | Echocardiography normal, pain NOT cardiac — likely fibromyalgia + disc protrusion L4-L5, cholesterol check recommended next visit |
| 06 | Aortic Aneurysm | EN | Male, smoker | 3 abdominal aortic aneurysms (incidental finding) | Discovered during gallstone MRI, 10 cigs/day (down from 25), echo normal, stress test normal, coronary scan ordered, incorrectly referred to cardiology instead of vascular surgery |
| 07 | Pre-Op Stent Statin | PL | Male, 119kg | Pre-op clearance, MI 2020 with 2 stents RCA | Stopped Lipitor 2 months ago due to muscle pain, cholesterol likely elevated, labs ordered in 4 weeks to assess statin intolerance, cleared for shoulder arthroscopy, significant overweight |
| 08 | Hypertension BP Monitor | PL | Male | Unstable hypertension, BP spike to ~200 | Amlodipine 5mg, BP now stable 130-137/70-80 morning, in-office 170/96 (white coat effect), echo + stress test normal, 24h ambulatory BP monitoring ordered, family history of hypertension |

## File Structure

```
demo/visits/
├── visit-01-coronarography-stenosis/
│   ├── raw-transcript.txt      # Raw scenario narrative
│   ├── dialogue.txt            # Doctor:/Patient: labeled dialogue
│   └── dialogue-tts.mp3        # ElevenLabs TTS (George + Sarah voices)
├── visit-02-gastric-bypass-preop/
│   ├── raw-transcript.txt
│   ├── dialogue.txt
│   └── dialogue-tts.mp3
├── visit-03-hypertension-followup/
│   └── ...
├── visit-04-chest-pain-carotid/
│   └── ...
├── visit-05-arm-pain-fibromyalgia/
│   └── ...
├── visit-06-aortic-aneurysm-smoking/
│   └── ...
├── visit-07-preop-stent-statin/
│   └── ...
└── visit-08-hypertension-bp-monitoring/
    └── ...
```

## Voices

- **Doctor**: George (JBFqnCBsd6RMkjVDRZzb) — Warm, middle-aged male, British accent
- **Female patients** (visits 01, 02, 04, 05): Sarah (EXAVITQu4vr4xnSDxMaL) — Mature, reassuring female
- **Male patients** (visits 03, 06, 07, 08): Eric (cjVigY5qzO86Huf0OWal) — Smooth, trustworthy male

## Usage

These TTS files serve as demo audio for the PostVisit.ai companion — they can be played through the app's visit recording flow to demonstrate the full pipeline:

1. Play `dialogue-tts.mp3` through the browser microphone → CompanionScribe records it
2. Whisper transcribes the TTS audio back to text
3. Claude processes the transcript into a structured visit summary
4. Patient can then ask follow-up questions about their visit

This creates a reproducible, controlled demo environment with realistic clinical dialogue.

## Regeneration

To regenerate TTS from dialogue files:

```bash
curl -X POST "https://api.elevenlabs.io/v1/text-to-dialogue?output_format=mp3_44100_128" \
  -H "Content-Type: application/json" \
  -H "xi-api-key: $ELEVENLABS_API_KEY" \
  -d '{
    "inputs": [
      {"text": "Doctor line here", "voice_id": "JBFqnCBsd6RMkjVDRZzb"},
      {"text": "Patient line here", "voice_id": "EXAVITQu4vr4xnSDxMaL"}
    ],
    "model_id": "eleven_v3",
    "language_code": "en"
  }' -o dialogue-tts.mp3
```

**Note:** ElevenLabs has a 5000 character limit per request. Longer dialogues (visits 01, 04) must be split into parts and concatenated with ffmpeg.
