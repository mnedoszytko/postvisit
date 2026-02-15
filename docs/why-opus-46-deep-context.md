# Why Opus 4.6: Deep Context — Full Patient Brain

## The Feature
PostVisit.ai loads the patient's complete medical history, clinical guidelines, and wearable data into a single AI prompt — up to 250,000+ tokens of context.

## Why Opus 4.6 Specifically
Opus 4.6 has a **1,000,000 token context window** — the largest of any production AI model. This enables loading a patient's entire medical record, multiple clinical guidelines, and comprehensive drug safety data simultaneously.

### What Deep Context Enables
- **Complete visit history**: All past visits with full SOAP notes, not just the last 3
- **Comprehensive drug safety**: Full FDA drug labels and adverse event reports, not truncated summaries
- **Multiple clinical guidelines**: 2-3 relevant PMC articles loaded simultaneously
- **3-month wearable data**: Complete Apple Watch health trends
- **Personal library**: Patient's own uploaded medical documents

### The Token Counter
A real-time token counter shows exactly how much context is loaded:
- Makes the invisible (context loading) visible and impressive
- Demonstrates mastery of the 1M context window
- Shows judges the technical depth of the implementation

### Previous Limitation
Standard models with 128K-200K context windows force aggressive truncation of medical data. Opus 4.6's 1M window eliminates this constraint entirely.

### Technical Details
- Context assembly: 7+ layers via ContextAssembler
- Token estimation: ~4 chars/token heuristic
- Breakdown tracking: per-layer token counts
- Display: collapsible context indicator in chat UI
- Opus46 tier: loads expanded data (all visits, full FDA labels)

### Context Layers
| Layer | Standard Tier | Opus 4.6 Tier |
|-------|--------------|---------------|
| Recent visits | Last 3, truncated assessments | ALL visits, full assessments |
| FDA labels | 500 char limit per section | 5,000 char limit, extra sections |
| Device data | 5-7 readings per metric | All available readings |
| Guidelines | Not loaded | Full PMC articles cached |
