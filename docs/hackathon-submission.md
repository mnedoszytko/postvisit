# Hackathon Submission — Project Description

PostVisit.ai is an AI-powered platform that bridges the gap between the doctor's office and everything that comes after. Built by a practicing cardiologist, it starts with a reverse AI scribe — the patient records the visit, and the system transcribes and structures it into clinical notes. From there, it assembles the full clinical context: not just the visit, but the patient's entire health record, vitals, lab history, medications, and curated clinical guidelines — giving patients an AI assistant to understand it all on their own terms.

The visit is just the starting point. The system uses Opus 4.6's 1M context window to assemble 8 layers of clinical data. Extended thinking enables calibrated reasoning across 15 AI services — from escalation detection to clinical reasoning pipelines with tool use. The AI calls 5 medical tools in real time: drug interactions (RxNorm), adverse events (OpenFDA), drug labels (DailyMed), guideline search, and lab reference ranges.

Tested in real hospital setting. Built in 7 days — coded by a cardiologist from Brussels to a train to Paris, a transatlantic flight, and San Francisco. 330 commits, 262 tests, 105 API endpoints, FHIR-aligned data model, HIPAA/GDPR compliance architecture.

Demo video: https://youtu.be/V29UCOii2jE
Live demo: https://postvisit.ai
