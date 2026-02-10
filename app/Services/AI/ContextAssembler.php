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

        $parts[] = "Visit Date: " . ($visit->started_at ?? 'Unknown');
        $parts[] = "Visit Type: " . ($visit->visit_type ?? 'General');
        $parts[] = "Reason for Visit: " . ($visit->reason_for_visit ?? 'Not specified');

        if ($visit->practitioner) {
            $parts[] = "Practitioner: Dr. {$visit->practitioner->first_name} {$visit->practitioner->last_name}";
            $parts[] = "Specialty: " . ($visit->practitioner->primary_specialty ?? 'General');
        }

        // Visit note (SOAP)
        if ($visit->visitNote) {
            $note = $visit->visitNote;
            if ($note->chief_complaint) {
                $parts[] = "\nChief Complaint: {$note->chief_complaint}";
            }
            if ($note->history_of_present_illness) {
                $parts[] = "\nHistory of Present Illness:\n{$note->history_of_present_illness}";
            }
            if ($note->review_of_systems) {
                $parts[] = "\nReview of Systems:\n{$note->review_of_systems}";
            }
            if ($note->physical_exam) {
                $parts[] = "\nPhysical Examination:\n{$note->physical_exam}";
            }
            if ($note->assessment) {
                $parts[] = "\nAssessment:\n{$note->assessment}";
            }
            if ($note->plan) {
                $parts[] = "\nPlan:\n{$note->plan}";
            }
            if ($note->follow_up) {
                $parts[] = "\nFollow-up:\n{$note->follow_up}";
            }
        }

        // Transcript
        if ($visit->transcript) {
            $transcript = $visit->transcript->clean_transcript ?? $visit->transcript->raw_transcript ?? '';
            if ($transcript && $transcript !== 'PLACEHOLDER - Awaiting real transcript from Dr. Nedo') {
                $parts[] = "\nVisit Transcript:\n{$transcript}";
            }
        }

        // Observations (test results)
        if ($visit->observations && $visit->observations->isNotEmpty()) {
            $parts[] = "\nTest Results & Observations:";
            foreach ($visit->observations as $obs) {
                $value = $obs->value_type === 'quantity'
                    ? "{$obs->value_quantity} {$obs->value_unit}"
                    : ($obs->value_string ?? '');
                $parts[] = "- {$obs->code_display}: {$value}" .
                    ($obs->interpretation ? " (interpretation: {$obs->interpretation})" : '') .
                    ($obs->reference_range_text ? " [ref: {$obs->reference_range_text}]" : '');
                if ($obs->specialty_data) {
                    $parts[] = "  Details: " . json_encode($obs->specialty_data, JSON_UNESCAPED_UNICODE);
                }
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
        $parts[] = "Date of Birth: " . ($patient->dob ?? 'Unknown');
        $parts[] = "Gender: " . ($patient->gender ?? 'Unknown');

        // Conditions from the visit
        $conditions = $visit->conditions;
        if ($conditions && $conditions->isNotEmpty()) {
            $parts[] = "\nKnown Conditions:";
            foreach ($conditions as $condition) {
                $parts[] = "- {$condition->code_display} ({$condition->code})" .
                    ($condition->clinical_status ? " — status: {$condition->clinical_status}" : '') .
                    ($condition->clinical_notes ? "\n  Notes: {$condition->clinical_notes}" : '');
            }
        }

        // Active prescriptions from the visit
        $prescriptions = $visit->prescriptions;
        if ($prescriptions && $prescriptions->isNotEmpty()) {
            $active = $prescriptions->where('status', 'active');
            if ($active->isNotEmpty()) {
                $parts[] = "\nCurrent Medications:";
                foreach ($active as $rx) {
                    $med = $rx->medication;
                    $name = $med ? $med->generic_name : 'Unknown medication';
                    $parts[] = "- {$name} {$rx->dose_quantity}{$rx->dose_unit} {$rx->frequency}" .
                        ($rx->frequency_text ? " ({$rx->frequency_text})" : '') .
                        ($rx->special_instructions ? "\n  Instructions: {$rx->special_instructions}" : '');
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

            $parts[] = "\nMedication: {$med->generic_name} ({$med->display_name})";
            if ($med->brand_names) {
                $parts[] = "Brand Names: " . implode(', ', (array) $med->brand_names);
            }
            $parts[] = "Prescribed Dose: {$rx->dose_quantity}{$rx->dose_unit}";
            $parts[] = "Route: " . ($rx->route ?? 'oral');
            $parts[] = "Frequency: " . ($rx->frequency ?? 'as directed');
            if ($rx->special_instructions) {
                $parts[] = "Special Instructions: {$rx->special_instructions}";
            }
            if ($rx->indication) {
                $parts[] = "Indication: {$rx->indication}";
            }
            if ($med->pregnancy_category) {
                $parts[] = "Pregnancy Category: {$med->pregnancy_category}";
            }
            if ($med->black_box_warning) {
                $parts[] = "WARNING: This medication has a black box warning.";
            }
        }

        $parts[] = "--- END MEDICATIONS DATA ---";

        return implode("\n", $parts);
    }
}
