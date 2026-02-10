<?php

namespace App\Services\AI;

use App\Models\Visit;

class ContextAssembler
{
    public function __construct(
        private PromptLoader $promptLoader,
    ) {}

    /**
     * Assemble the full context for an AI call about a specific visit.
     *
     * Returns a system prompt and an array of context messages that form
     * the static context layers (visit data, patient record, guidelines).
     * Conversation history is NOT included — the caller appends it.
     *
     * @return array{system_prompt: string, context_messages: array}
     */
    public function assembleForVisit(Visit $visit, string $promptName = 'qa-assistant'): array
    {
        $systemPrompt = $this->promptLoader->load($promptName);

        $contextMessages = [];

        // Layer 1: Visit data
        $contextMessages[] = [
            'role' => 'user',
            'content' => $this->formatVisitContext($visit),
        ];

        // Layer 2: Patient record
        $contextMessages[] = [
            'role' => 'user',
            'content' => $this->formatPatientContext($visit),
        ];

        // Layer 3: Clinical guidelines
        $guidelines = $this->formatGuidelinesContext($visit);
        if ($guidelines) {
            $contextMessages[] = [
                'role' => 'user',
                'content' => $guidelines,
            ];
        }

        // Layer 4: Medications data
        $medsContext = $this->formatMedicationsContext($visit);
        if ($medsContext) {
            $contextMessages[] = [
                'role' => 'user',
                'content' => $medsContext,
            ];
        }

        // Acknowledge context load
        $contextMessages[] = [
            'role' => 'assistant',
            'content' => 'I have loaded the full visit context, patient record, clinical guidelines, and medication data. I am ready to assist the patient with questions about this visit.',
        ];

        return [
            'system_prompt' => $systemPrompt,
            'context_messages' => $contextMessages,
        ];
    }

    private function formatVisitContext(Visit $visit): string
    {
        $parts = ["--- VISIT DATA ---"];

        $parts[] = "Visit Date: " . ($visit->visit_date ?? 'Unknown');
        $parts[] = "Specialty: " . ($visit->specialty ?? 'General');

        if ($visit->practitioner) {
            $parts[] = "Practitioner: Dr. " . $visit->practitioner->full_name;
        }

        // Structured visit notes
        if ($visit->visitNote) {
            $note = $visit->visitNote;
            if ($note->structured_sections) {
                $parts[] = "\nStructured Visit Sections:";
                $parts[] = json_encode($note->structured_sections, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            }
            if ($note->soap_note) {
                $parts[] = "\nSOAP Note:";
                $parts[] = json_encode($note->soap_note, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            }
        }

        // Transcript
        if ($visit->transcript) {
            $parts[] = "\nVisit Transcript:";
            $parts[] = $visit->transcript->clean_text ?? $visit->transcript->raw_text ?? '';
        }

        // Observations (test results)
        if ($visit->observations && $visit->observations->isNotEmpty()) {
            $parts[] = "\nTest Results & Observations:";
            foreach ($visit->observations as $obs) {
                $parts[] = "- {$obs->display_name}: {$obs->value} {$obs->unit}" .
                    ($obs->interpretation ? " ({$obs->interpretation})" : '');
            }
        }

        $parts[] = "--- END VISIT DATA ---";

        return implode("\n", $parts);
    }

    private function formatPatientContext(Visit $visit): string
    {
        $patient = $visit->patient;
        if (! $patient) {
            return "--- PATIENT RECORD ---\nNo patient record available.\n--- END PATIENT RECORD ---";
        }

        $parts = ["--- PATIENT RECORD ---"];
        $parts[] = "Name: {$patient->first_name} {$patient->last_name}";
        $parts[] = "Date of Birth: " . ($patient->date_of_birth ?? 'Unknown');
        $parts[] = "Sex: " . ($patient->sex ?? 'Unknown');

        // Conditions
        if ($patient->conditions && $patient->conditions->isNotEmpty()) {
            $parts[] = "\nKnown Conditions:";
            foreach ($patient->conditions as $condition) {
                $parts[] = "- {$condition->display_name}" .
                    ($condition->clinical_status ? " ({$condition->clinical_status})" : '');
            }
        }

        // Active prescriptions
        if ($patient->prescriptions && $patient->prescriptions->isNotEmpty()) {
            $active = $patient->prescriptions->where('status', 'active');
            if ($active->isNotEmpty()) {
                $parts[] = "\nCurrent Medications:";
                foreach ($active as $rx) {
                    $med = $rx->medication;
                    $parts[] = "- " . ($med ? $med->generic_name : $rx->display_name) .
                        " {$rx->dosage_text}";
                }
            }
        }

        $parts[] = "--- END PATIENT RECORD ---";

        return implode("\n", $parts);
    }

    private function formatGuidelinesContext(Visit $visit): ?string
    {
        $guidelinesTemplate = $this->promptLoader->load('context-guidelines');

        // For now, return the template. In production, this would load
        // actual guideline documents based on the visit's specialty and diagnoses.
        return "--- CLINICAL GUIDELINES CONTEXT ---\n{$guidelinesTemplate}\n--- END GUIDELINES ---";
    }

    private function formatMedicationsContext(Visit $visit): ?string
    {
        $prescriptions = $visit->prescriptions;
        if (! $prescriptions || $prescriptions->isEmpty()) {
            return null;
        }

        $parts = ["--- MEDICATIONS DATA ---"];

        foreach ($prescriptions as $rx) {
            $med = $rx->medication;
            if (! $med) {
                continue;
            }

            $parts[] = "\nMedication: {$med->generic_name}";
            if ($med->brand_names) {
                $parts[] = "Brand Names: " . implode(', ', (array) $med->brand_names);
            }
            $parts[] = "Prescribed Dose: {$rx->dosage_text}";
            $parts[] = "Route: " . ($rx->route ?? 'oral');
            $parts[] = "Frequency: " . ($rx->frequency ?? 'as directed');

            if ($med->side_effects) {
                $parts[] = "Known Side Effects: " . json_encode($med->side_effects);
            }
            if ($med->contraindications) {
                $parts[] = "Contraindications: " . json_encode($med->contraindications);
            }
        }

        $parts[] = "--- END MEDICATIONS DATA ---";

        return implode("\n", $parts);
    }
}
