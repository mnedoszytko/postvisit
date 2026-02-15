# Why Opus 4.6: Comprehensive Patient Education

## The Feature
PostVisit.ai generates comprehensive, multi-section patient education documents -- personalized health guides that cover everything a patient needs to know after their visit.

## Why Opus 4.6 Specifically
Opus 4.6 supports up to **128K output tokens** -- 8x more than previous models. This enables generating complete, thorough patient education documents in a single request.

### What 128K Output Enables
- **Complete medication guides**: Full drug information, interactions, side effects for ALL prescribed medications
- **Condition education**: Detailed explanations of each diagnosed condition, causes, prognosis
- **Personalized lifestyle recommendations**: Diet, exercise, and habit changes specific to the patient's conditions
- **Comprehensive glossary**: Every medical term explained in plain language
- **Follow-up preparation**: Suggested questions for the next appointment

### Previous Limitation
With 16K output tokens (standard), education documents had to be truncated or split into multiple requests. Opus 4.6's 128K capacity allows a single, complete document generation.

### Technical Details
- Endpoint: `POST /api/v1/visits/{visit}/education`
- Output budget: 65,536 tokens (50% of 128K capacity)
- Thinking budget: 10,000 tokens (Opus 4.6 reasoning tier)
- Streaming: SSE for real-time document generation
- Full context: Uses all 7 context layers from ContextAssembler
