<?php

namespace App\Services\AI;

use Anthropic\Messages\CacheControlEphemeral;
use Anthropic\Messages\TextBlockParam;
use App\Models\Visit;
use App\Services\Medications\OpenFdaClient;
use Illuminate\Support\Facades\Log;

class ContextAssembler
{
    public function __construct(
        private PromptLoader $promptLoader,
        private OpenFdaClient $openFda,
    ) {}

    /**
     * Assemble the full context for an AI call about a specific visit.
     *
     * Returns a system prompt (as cacheable TextBlockParam array) and
     * context messages with visit-specific data.
     *
     * @return array{system_prompt: array|string, context_messages: array}
     */
    public function assembleForVisit(Visit $visit, string $promptName = 'qa-assistant'): array
    {
        $cacheEnabled = config('anthropic.cache.enabled', true);
        $cacheTtl = config('anthropic.cache.ttl', '5m');

        $systemPrompt = $this->promptLoader->load($promptName);

        if ($cacheEnabled) {
            $systemBlocks = $this->buildCacheableSystemBlocks($systemPrompt, $visit, $cacheTtl);
        } else {
            $systemBlocks = $systemPrompt;
        }

        $contextMessages = [];

        // Layer 1: Visit data (per-request, not cacheable)
        $contextMessages[] = [
            'role' => 'user',
            'content' => $this->formatVisitContext($visit),
        ];

        // Layer 2: Patient record
        $contextMessages[] = [
            'role' => 'user',
            'content' => $this->formatPatientContext($visit),
        ];

        // Layer 3: Medications data
        $medsContext = $this->formatMedicationsContext($visit);
        if ($medsContext) {
            $contextMessages[] = [
                'role' => 'user',
                'content' => $medsContext,
            ];
        }

        // Layer 4: FDA safety data (adverse events, labels)
        $fdaContext = $this->formatFdaSafetyContext($visit);
        if ($fdaContext) {
            $contextMessages[] = [
                'role' => 'user',
                'content' => $fdaContext,
            ];
        }

        // Acknowledge context load
        $contextMessages[] = [
            'role' => 'assistant',
            'content' => 'I have loaded the full visit context, patient record, clinical guidelines, medication data, and FDA safety information. I am ready to assist the patient with questions about this visit.',
        ];

        return [
            'system_prompt' => $systemBlocks,
            'context_messages' => $contextMessages,
        ];
    }

    /**
     * Build system prompt as TextBlockParam[] with cache_control on stable blocks.
     *
     * The system prompt and clinical guidelines are stable across requests
     * for the same visit type, so they benefit from prompt caching.
     *
     * @return TextBlockParam[]
     */
    private function buildCacheableSystemBlocks(string $systemPrompt, Visit $visit, string $ttl): array
    {
        $blocks = [];

        // Block 1: System prompt (cacheable — same per prompt type)
        $blocks[] = TextBlockParam::with(
            text: $systemPrompt,
            cacheControl: CacheControlEphemeral::with(ttl: $ttl),
        );

        // Block 2: Clinical guidelines (cacheable — stable reference material)
        $guidelines = $this->loadGuidelinesContent($visit);
        if ($guidelines) {
            $blocks[] = TextBlockParam::with(
                text: $guidelines,
                cacheControl: CacheControlEphemeral::with(ttl: $ttl),
            );
        }

        return $blocks;
    }

    /**
     * Load real clinical guideline files from demo/guidelines/.
     *
     * Guidelines are loaded into the system prompt (cacheable) rather than
     * as user messages, because they are stable reference material that
     * doesn't change between requests.
     */
    private function loadGuidelinesContent(Visit $visit): ?string
    {
        $guidelinesPath = base_path('demo/guidelines');

        if (! is_dir($guidelinesPath)) {
            // Fallback to the template format
            $template = $this->promptLoader->load('context-guidelines');

            return "--- CLINICAL GUIDELINES CONTEXT ---\n{$template}\n--- END GUIDELINES ---";
        }

        $files = glob($guidelinesPath.'/*.md');
        if (empty($files)) {
            return null;
        }

        $parts = ["--- CLINICAL GUIDELINES CONTEXT ---\n"];

        foreach ($files as $file) {
            $content = file_get_contents($file);
            if ($content) {
                $parts[] = $content;
                $parts[] = "\n---\n";
            }
        }

        $parts[] = '--- END CLINICAL GUIDELINES ---';

        $combined = implode("\n", $parts);

        Log::channel('ai')->debug('Loaded clinical guidelines into system prompt', [
            'files' => array_map('basename', $files),
            'total_chars' => strlen($combined),
        ]);

        return $combined;
    }

    private function formatVisitContext(Visit $visit): string
    {
        $parts = ['--- VISIT DATA ---'];

        $parts[] = 'Visit Date: '.($visit->started_at ?? 'Unknown');
        $parts[] = 'Visit Type: '.($visit->visit_type ?? 'General');
        $parts[] = 'Reason for Visit: '.($visit->reason_for_visit ?? 'Not specified');

        if ($visit->practitioner) {
            $parts[] = "Practitioner: Dr. {$visit->practitioner->first_name} {$visit->practitioner->last_name}";
            $parts[] = 'Specialty: '.($visit->practitioner->primary_specialty ?? 'General');
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
                $parts[] = "- {$obs->code_display}: {$value}".
                    ($obs->interpretation ? " (interpretation: {$obs->interpretation})" : '').
                    ($obs->reference_range_text ? " [ref: {$obs->reference_range_text}]" : '');
                if ($obs->specialty_data) {
                    $parts[] = '  Details: '.json_encode($obs->specialty_data, JSON_UNESCAPED_UNICODE);
                }
            }
        }

        $parts[] = '--- END VISIT DATA ---';

        return implode("\n", $parts);
    }

    private function formatPatientContext(Visit $visit): string
    {
        $patient = $visit->patient;
        if (! $patient) {
            return "--- PATIENT RECORD ---\nNo patient record available.\n--- END PATIENT RECORD ---";
        }

        $parts = ['--- PATIENT RECORD ---'];
        $parts[] = "Name: {$patient->first_name} {$patient->last_name}";
        $parts[] = 'Date of Birth: '.($patient->dob ?? 'Unknown');
        $parts[] = 'Gender: '.($patient->gender ?? 'Unknown');

        // Conditions from the visit
        $conditions = $visit->conditions;
        if ($conditions && $conditions->isNotEmpty()) {
            $parts[] = "\nKnown Conditions:";
            foreach ($conditions as $condition) {
                $parts[] = "- {$condition->code_display} ({$condition->code})".
                    ($condition->clinical_status ? " — status: {$condition->clinical_status}" : '').
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
                    $parts[] = "- {$name} {$rx->dose_quantity}{$rx->dose_unit} {$rx->frequency}".
                        ($rx->frequency_text ? " ({$rx->frequency_text})" : '').
                        ($rx->special_instructions ? "\n  Instructions: {$rx->special_instructions}" : '');
                }
            }
        }

        $parts[] = '--- END PATIENT RECORD ---';

        return implode("\n", $parts);
    }

    private function formatMedicationsContext(Visit $visit): ?string
    {
        $prescriptions = $visit->prescriptions;
        if (! $prescriptions || $prescriptions->isEmpty()) {
            return null;
        }

        $parts = ['--- MEDICATIONS DATA ---'];

        foreach ($prescriptions as $rx) {
            $med = $rx->medication;
            if (! $med) {
                continue;
            }

            $parts[] = "\nMedication: {$med->generic_name} ({$med->display_name})";
            if ($med->brand_names) {
                $parts[] = 'Brand Names: '.implode(', ', (array) $med->brand_names);
            }
            $parts[] = "Prescribed Dose: {$rx->dose_quantity}{$rx->dose_unit}";
            $parts[] = 'Route: '.($rx->route ?? 'oral');
            $parts[] = 'Frequency: '.($rx->frequency ?? 'as directed');
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
                $parts[] = 'WARNING: This medication has a black box warning.';
            }
        }

        $parts[] = '--- END MEDICATIONS DATA ---';

        return implode("\n", $parts);
    }

    private function formatFdaSafetyContext(Visit $visit): ?string
    {
        $prescriptions = $visit->prescriptions;
        if (! $prescriptions || $prescriptions->isEmpty()) {
            return null;
        }

        $parts = ['--- FDA SAFETY DATA ---'];
        $hasData = false;

        foreach ($prescriptions as $rx) {
            $med = $rx->medication;
            if (! $med || ! $med->generic_name) {
                continue;
            }

            try {
                // Get top adverse events from OpenFDA FAERS database
                $adverse = $this->openFda->getAdverseEvents($med->generic_name, 5);
                if (! empty($adverse['events'])) {
                    $hasData = true;
                    $parts[] = "\nFDA Adverse Event Reports for {$med->generic_name}:";
                    foreach ($adverse['events'] as $event) {
                        $parts[] = "- {$event['reaction']}: {$event['count']} reports";
                    }
                }

                // Get key label sections
                $label = $this->openFda->getDrugLabel($med->generic_name);
                if (! empty($label)) {
                    $hasData = true;
                    if (! empty($label['boxed_warning'])) {
                        $parts[] = "\nBOXED WARNING for {$med->generic_name}: ".mb_substr($label['boxed_warning'], 0, 500);
                    }
                    if (! empty($label['information_for_patients'])) {
                        $parts[] = "\nPatient Information for {$med->generic_name}: ".mb_substr($label['information_for_patients'], 0, 500);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to fetch FDA data for context', [
                    'medication' => $med->generic_name,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (! $hasData) {
            return null;
        }

        $parts[] = '--- END FDA SAFETY DATA ---';

        return implode("\n", $parts);
    }
}
