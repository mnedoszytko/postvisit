<?php

namespace App\Services\AI;

use Anthropic\Messages\CacheControlEphemeral;
use Anthropic\Messages\TextBlockParam;
use App\Enums\AiTier;
use App\Models\PatientContextSummary;
use App\Models\Visit;
use App\Services\Guidelines\GuidelinesRepository;
use App\Services\Medications\OpenFdaClient;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ContextAssembler
{
    /**
     * Per-layer token breakdown from the last assembleForVisit() call.
     *
     * @var array<string, int>
     */
    private array $tokenBreakdown = [];

    public function __construct(
        private PromptLoader $promptLoader,
        private OpenFdaClient $openFda,
        private AiTierManager $tierManager,
        private GuidelinesRepository $guidelines,
    ) {}

    /**
     * Get the token breakdown from the last assembleForVisit() call.
     *
     * @return array<string, int>
     */
    public function getTokenBreakdown(): array
    {
        return $this->tokenBreakdown;
    }

    /**
     * Estimate token count for a string (~4 chars per token).
     */
    public function estimateTokens(string $text): int
    {
        return (int) ceil(mb_strlen($text) / 4);
    }

    /**
     * Assemble minimal context for a quick first response.
     * Skips FDA safety data, library items, and guidelines for speed.
     *
     * @return array{system_prompt: string, context_messages: array}
     */
    public function assembleQuickContext(Visit $visit): array
    {
        $visit->loadMissing(['patient', 'practitioner', 'visitNote', 'conditions', 'prescriptions.medication']);

        $systemPrompt = $this->promptLoader->load('qa-assistant-quick');

        $contextMessages = [
            ['role' => 'user', 'content' => $this->formatVisitContext($visit)],
            ['role' => 'user', 'content' => $this->formatPatientContext($visit)],
        ];

        // Include health history and device data even in quick context for richer answers
        $healthHistoryContext = $this->formatHealthHistoryContext($visit);
        if ($healthHistoryContext) {
            $contextMessages[] = ['role' => 'user', 'content' => $healthHistoryContext];
        }

        $deviceContext = $this->formatDeviceDataContext($visit);
        if ($deviceContext) {
            $contextMessages[] = ['role' => 'user', 'content' => $deviceContext];
        }

        $contextMessages[] = ['role' => 'assistant', 'content' => 'I have the visit context. Ready to help the patient.'];

        return ['system_prompt' => $systemPrompt, 'context_messages' => $contextMessages];
    }

    /**
     * Assemble the full context for an AI call about a specific visit.
     *
     * Returns a system prompt (as cacheable TextBlockParam array) and
     * context messages with visit-specific data. Tracks per-layer token
     * counts accessible via getTokenBreakdown().
     *
     * @return array{system_prompt: array|string, context_messages: array}
     */
    public function assembleForVisit(Visit $visit, string $promptName = 'qa-assistant'): array
    {
        // Reset token breakdown for this assembly
        $this->tokenBreakdown = [];

        // Eager load all relationships upfront to prevent N+1 queries
        $visit->loadMissing(['patient', 'practitioner', 'visitNote', 'observations', 'transcript', 'conditions', 'prescriptions.medication']);

        $tier = $this->tierManager->current();
        $isOpus = $tier === AiTier::Opus46;
        $cacheTtl = config('anthropic.cache.ttl', '5m');

        $systemPrompt = $this->promptLoader->load($promptName);
        $this->tokenBreakdown['system_prompt'] = $this->estimateTokens($systemPrompt);

        if ($tier->cachingEnabled()) {
            $systemBlocks = $this->buildCacheableSystemBlocks($systemPrompt, $visit, $cacheTtl, $tier->guidelinesEnabled());
        } else {
            $systemBlocks = $systemPrompt;
        }

        $contextMessages = [];

        // Layer 1: Visit data (per-request, not cacheable)
        $visitContext = $this->formatVisitContext($visit);
        $this->tokenBreakdown['visit_data'] = $this->estimateTokens($visitContext);
        $contextMessages[] = [
            'role' => 'user',
            'content' => $visitContext,
        ];

        // Layer 2: Patient record
        $patientContext = $this->formatPatientContext($visit);
        $this->tokenBreakdown['patient_record'] = $this->estimateTokens($patientContext);
        $contextMessages[] = [
            'role' => 'user',
            'content' => $patientContext,
        ];

        // Layer 2b: Full health history (observations across all visits, last 3 months)
        if (config('anthropic.context_layers.health_history', true)) {
            $healthHistoryContext = $this->formatHealthHistoryContext($visit);
            if ($healthHistoryContext) {
                $this->tokenBreakdown['health_history'] = $this->estimateTokens($healthHistoryContext);
                $contextMessages[] = [
                    'role' => 'user',
                    'content' => $healthHistoryContext,
                ];
            }
        }

        // Layer 2c: Recent visit summaries
        if (config('anthropic.context_layers.recent_visits', true)) {
            // Opus 4.6: cap at 5 visits, standard: last 3
            $recentVisitsContext = $this->formatRecentVisitsContext($visit, $isOpus ? 5 : 3);
            if ($recentVisitsContext) {
                $this->tokenBreakdown['recent_visits'] = $this->estimateTokens($recentVisitsContext);
                $contextMessages[] = [
                    'role' => 'user',
                    'content' => $recentVisitsContext,
                ];
            }
        }

        // Layer 2d: Device/wearable data (Apple Watch)
        if (config('anthropic.context_layers.device_data', true)) {
            $deviceContext = $this->formatDeviceDataContext($visit, $isOpus);
            if ($deviceContext) {
                $this->tokenBreakdown['device_data'] = $this->estimateTokens($deviceContext);
                $contextMessages[] = [
                    'role' => 'user',
                    'content' => $deviceContext,
                ];
            }
        }

        // Layer 3: Medications data
        if (config('anthropic.context_layers.medications', true)) {
            $medsContext = $this->formatMedicationsContext($visit);
            if ($medsContext) {
                $this->tokenBreakdown['medications'] = $this->estimateTokens($medsContext);
                $contextMessages[] = [
                    'role' => 'user',
                    'content' => $medsContext,
                ];
            }
        }

        // Layer 4: FDA safety data (adverse events, labels)
        if (config('anthropic.context_layers.fda_safety', true)) {
            $fdaContext = $this->formatFdaSafetyContext($visit, $isOpus);
            if ($fdaContext) {
                $this->tokenBreakdown['fda_safety'] = $this->estimateTokens($fdaContext);
                $contextMessages[] = [
                    'role' => 'user',
                    'content' => $fdaContext,
                ];
            }
        }

        // Layer 5: User's personal library (analyzed documents)
        if (config('anthropic.context_layers.library', true)) {
            $libraryContext = $this->formatLibraryContext($visit);
            if ($libraryContext) {
                $this->tokenBreakdown['personal_library'] = $this->estimateTokens($libraryContext);
                $contextMessages[] = [
                    'role' => 'user',
                    'content' => $libraryContext,
                ];
            }
        }

        // Layer 6: Historical context summaries (opt-in)
        if (config('anthropic.context_layers.compaction', true)) {
            $compactionContext = $this->formatContextCompactionLayer($visit);
            if ($compactionContext) {
                $this->tokenBreakdown['context_compaction'] = $this->estimateTokens($compactionContext);
                $contextMessages[] = [
                    'role' => 'user',
                    'content' => $compactionContext,
                ];
            }
        }

        // Calculate total
        $this->tokenBreakdown['total'] = array_sum($this->tokenBreakdown);

        // Acknowledge context load
        $contextMessages[] = [
            'role' => 'assistant',
            'content' => 'I have loaded the full visit context, patient record, health history with trends, recent visit summaries, device/wearable data, clinical guidelines, medication data, FDA safety information, and your personal medical library. I am ready to assist the patient with questions about this visit.',
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
    private function buildCacheableSystemBlocks(string $systemPrompt, Visit $visit, string $ttl, bool $includeGuidelines = true): array
    {
        $blocks = [];

        // Block 1: System prompt (cacheable — same per prompt type)
        $blocks[] = TextBlockParam::with(
            text: $systemPrompt,
            cacheControl: CacheControlEphemeral::with(ttl: $ttl),
        );

        // Block 2: Clinical guidelines (cacheable — stable reference material, Opus 4.6 tier only)
        $guidelines = $includeGuidelines ? $this->loadGuidelinesContent($visit) : null;
        if ($guidelines) {
            $this->tokenBreakdown['guidelines'] = $this->estimateTokens($guidelines);
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
        $context = $this->guidelines->getRelevantGuidelines($visit);

        if ($context === '') {
            return null;
        }

        return $context;
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
            if ($transcript && ! str_starts_with($transcript, 'PLACEHOLDER')) {
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

        // Biometrics
        if ($patient->height_cm) {
            $parts[] = "Height: {$patient->height_cm} cm";
        }
        if ($patient->weight_kg) {
            $parts[] = "Weight: {$patient->weight_kg} kg";
            if ($patient->height_cm) {
                $meters = $patient->height_cm / 100;
                $bmi = round($patient->weight_kg / ($meters * $meters), 1);
                $parts[] = "BMI: {$bmi}";
            }
        }
        if ($patient->blood_type) {
            $parts[] = "Blood Type: {$patient->blood_type}";
        }

        // Allergies
        $allergies = $patient->allergies;
        if (! empty($allergies) && is_array($allergies)) {
            $parts[] = "\nAllergies:";
            foreach ($allergies as $allergy) {
                $name = $allergy['name'] ?? 'Unknown';
                $severity = ! empty($allergy['severity']) ? " ({$allergy['severity']})" : '';
                $parts[] = "- {$name}{$severity}";
            }
        }

        // Conditions from the visit
        $conditions = $visit->conditions;
        if ($conditions && $conditions->isNotEmpty()) {
            $parts[] = "\nKnown Conditions:";
            foreach ($conditions as $condition) {
                $parts[] = "- {$condition->code_display} ({$condition->code})".
                    ($condition->clinical_status ? " — status: {$condition->clinical_status}" : '').
                    ($condition->severity ? ", severity: {$condition->severity}" : '').
                    ($condition->onset_date ? ", onset: {$condition->onset_date->format('Y-m-d')}" : '').
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

    /**
     * Format FDA safety context.
     *
     * @param  bool  $fullLabels  When true (Opus 4.6), loads full drug labels with 5000 char limit and extra sections
     */
    private function formatFdaSafetyContext(Visit $visit, bool $fullLabels = false): ?string
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

            $medSafetyContext = $this->getFdaSafetyForMedication($med->generic_name, $fullLabels);
            if ($medSafetyContext) {
                $hasData = true;
                $parts[] = $medSafetyContext;
            }
        }

        if (! $hasData) {
            return null;
        }

        $parts[] = '--- END FDA SAFETY DATA ---';

        return implode("\n", $parts);
    }

    /**
     * Get cached FDA safety context for a single medication.
     *
     * Caches the formatted safety string for 24 hours to avoid repeated
     * FDA API calls across chat sessions for the same medication.
     *
     * @param  bool  $fullLabels  When true (Opus 4.6), uses 5000 char limit and includes extra label sections
     */
    private function getFdaSafetyForMedication(string $genericName, bool $fullLabels = false): ?string
    {
        $cacheKey = 'fda_safety:'.($fullLabels ? 'full:' : '').mb_strtolower($genericName);
        $charLimit = $fullLabels ? 5000 : 500;

        return Cache::remember($cacheKey, 86400, function () use ($genericName, $fullLabels, $charLimit): ?string {
            $parts = [];

            try {
                $adverse = $this->openFda->getAdverseEvents($genericName, 5);
                if (! empty($adverse['events'])) {
                    $parts[] = "\nFDA Adverse Event Reports for {$genericName}:";
                    foreach ($adverse['events'] as $event) {
                        $parts[] = "- {$event['reaction']}: {$event['count']} reports";
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('FDA adverse events lookup failed, skipping', [
                    'medication' => $genericName,
                    'error' => $e->getMessage(),
                ]);
            }

            try {
                $label = $this->openFda->getDrugLabel($genericName);
                if (! empty($label)) {
                    if (! empty($label['boxed_warning'])) {
                        $parts[] = "\nBOXED WARNING for {$genericName}: ".mb_substr($label['boxed_warning'], 0, $charLimit);
                    }
                    if (! empty($label['information_for_patients'])) {
                        $parts[] = "\nPatient Information for {$genericName}: ".mb_substr($label['information_for_patients'], 0, $charLimit);
                    }

                    // Opus 4.6: include extra label sections for comprehensive drug context
                    if ($fullLabels) {
                        if (! empty($label['warnings_and_cautions'])) {
                            $parts[] = "\nWarnings & Cautions for {$genericName}: ".mb_substr($label['warnings_and_cautions'], 0, $charLimit);
                        }
                        if (! empty($label['drug_interactions'])) {
                            $parts[] = "\nDrug Interactions for {$genericName}: ".mb_substr($label['drug_interactions'], 0, $charLimit);
                        }
                        if (! empty($label['adverse_reactions'])) {
                            $parts[] = "\nAdverse Reactions for {$genericName}: ".mb_substr($label['adverse_reactions'], 0, $charLimit);
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('FDA drug label lookup failed, skipping', [
                    'medication' => $genericName,
                    'error' => $e->getMessage(),
                ]);
            }

            return $parts !== [] ? implode("\n", $parts) : null;
        });
    }

    /**
     * Format patient's observation history (last 3 months) grouped by type with trends.
     *
     * Queries ALL patient observations, not just those from the current visit,
     * to give the AI a complete picture of health trends.
     */
    private function formatHealthHistoryContext(Visit $visit): ?string
    {
        $patient = $visit->patient;
        if (! $patient) {
            return null;
        }

        // Post-judging: 1 month lookback, max 50 observations for speed
        $lookback = now()->subMonth();

        // Get patient observations from the last month, excluding current visit
        // (current visit observations are already in formatVisitContext)
        $observations = $patient->observations()
            ->where('effective_date', '>=', $lookback)
            ->where(function ($query) use ($visit) {
                $query->where('visit_id', '!=', $visit->id)
                    ->orWhereNull('visit_id');
            })
            ->orderBy('effective_date', 'desc')
            ->limit(50)
            ->get();

        if ($observations->isEmpty()) {
            return null;
        }

        $parts = ['--- HEALTH HISTORY (Last 3 Months) ---'];

        // Group by category (vital-signs, laboratory, etc.)
        $grouped = $observations->groupBy('category');

        foreach ($grouped as $category => $categoryObs) {
            $parts[] = '';
            $parts[] = strtoupper(str_replace('-', ' ', $category)).':';

            // Sub-group by code_display (e.g. "Blood Pressure", "Heart Rate")
            $byType = $categoryObs->groupBy('code_display');

            foreach ($byType as $typeName => $typeObs) {
                $trend = $this->formatObservationTrend($typeObs);
                $parts[] = "  {$typeName}: {$trend}";
            }
        }

        // Also include ALL patient conditions (not just visit-scoped)
        $allConditions = $patient->conditions()
            ->where('clinical_status', '!=', 'resolved')
            ->orderBy('onset_date')
            ->get();

        if ($allConditions->isNotEmpty()) {
            $parts[] = '';
            $parts[] = 'ALL ACTIVE CONDITIONS:';
            foreach ($allConditions as $condition) {
                $line = "  - {$condition->code_display} ({$condition->code})";
                $line .= $condition->clinical_status ? " — {$condition->clinical_status}" : '';
                $line .= $condition->severity ? ", {$condition->severity}" : '';
                $line .= $condition->onset_date ? ", since {$condition->onset_date->format('Y-m-d')}" : '';
                $parts[] = $line;
            }
        }

        $parts[] = '--- END HEALTH HISTORY ---';

        return implode("\n", $parts);
    }

    /**
     * Format a collection of same-type observations as a trend string.
     *
     * Example: "130/85 (Jan 15) → 125/80 (Feb 1) → 122/78 (Feb 10)"
     */
    private function formatObservationTrend(Collection $observations): string
    {
        $points = [];
        foreach ($observations as $obs) {
            $value = $obs->value_type === 'quantity'
                ? "{$obs->value_quantity} {$obs->value_unit}"
                : ($obs->value_string ?? '');

            $date = $obs->effective_date->format('M j');

            $interp = '';
            if ($obs->interpretation) {
                $interpLabels = ['L' => 'low', 'LL' => 'critically low', 'H' => 'high', 'HH' => 'critically high', 'N' => 'normal'];
                $interp = ' ['.($interpLabels[$obs->interpretation] ?? $obs->interpretation).']';
            }

            $ref = $obs->reference_range_text ? " (ref: {$obs->reference_range_text})" : '';

            $points[] = "{$value} ({$date}){$interp}{$ref}";
        }

        return implode(' → ', $points);
    }

    /**
     * Format recent visit summaries (excluding current).
     *
     * @param  int|null  $limit  Number of recent visits to include (null = all visits)
     */
    private function formatRecentVisitsContext(Visit $visit, ?int $limit = 3): ?string
    {
        $patient = $visit->patient;
        if (! $patient) {
            return null;
        }

        $query = $patient->visits()
            ->where('id', '!=', $visit->id)
            ->with(['practitioner', 'visitNote'])
            ->latest('started_at');

        if ($limit !== null) {
            $query->limit($limit);
        }

        $recentVisits = $query->get();

        if ($recentVisits->isEmpty()) {
            return null;
        }

        $parts = ['--- RECENT VISITS ---'];

        foreach ($recentVisits as $rv) {
            $date = $rv->started_at ? $rv->started_at->format('M j, Y') : 'Unknown date';
            $type = $rv->visit_type ?? 'General';
            $reason = $rv->reason_for_visit ?? 'Not specified';

            $practitioner = '';
            if ($rv->practitioner) {
                $practitioner = " with Dr. {$rv->practitioner->first_name} {$rv->practitioner->last_name}";
                if ($rv->practitioner->primary_specialty) {
                    $practitioner .= " ({$rv->practitioner->primary_specialty})";
                }
            }

            $parts[] = '';
            $parts[] = "{$date} — {$type}{$practitioner}";
            $parts[] = "  Reason: {$reason}";

            // Include assessment from visit note if available
            // Opus 4.6 (no limit): full assessment; standard (limit=3): truncated to 300 chars
            if ($rv->visitNote && $rv->visitNote->assessment) {
                $assessment = $limit === null
                    ? $rv->visitNote->assessment
                    : mb_substr($rv->visitNote->assessment, 0, 300);
                $parts[] = "  Assessment: {$assessment}";
            }
        }

        $parts[] = '--- END RECENT VISITS ---';

        return implode("\n", $parts);
    }

    /**
     * Format Apple Watch / wearable device data from static JSON file.
     *
     * For the demo, device data is loaded from public/data/apple-watch-{patient}.json.
     * In production, this would come from HealthKit API integration.
     *
     * @param  bool  $expanded  When true (Opus 4.6), loads all available readings instead of limiting to 5-7
     */
    private function formatDeviceDataContext(Visit $visit, bool $expanded = false): ?string
    {
        $patient = $visit->patient;
        if (! $patient) {
            return null;
        }

        // Try to load device data from static JSON using patient's FHIR ID
        $patientSlug = $patient->fhir_patient_id;
        $filePath = public_path("data/apple-watch-{$patientSlug}.json");

        if (! File::exists($filePath)) {
            return null;
        }

        try {
            $data = json_decode(File::get($filePath), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            Log::warning('Failed to parse device data JSON', [
                'file' => $filePath,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        // Opus 4.6: all readings; standard: limited to 5-7
        $readingLimit = $expanded ? 999 : 5;
        $dailyLimit = $expanded ? 999 : 7;

        $parts = ['--- WEARABLE DEVICE DATA ---'];
        $parts[] = 'Source: '.($data['device']['type'] ?? 'Unknown device');
        $parts[] = 'Last Sync: '.($data['device']['last_sync'] ?? 'Unknown');

        // Heart Rate
        if (! empty($data['heart_rate'])) {
            $parts[] = '';
            $parts[] = 'HEART RATE:';
            $parts[] = "  Resting Average: {$data['heart_rate']['resting_average_bpm']} bpm";
            if (! empty($data['heart_rate']['readings'])) {
                $recent = array_slice($data['heart_rate']['readings'], 0, $readingLimit);
                foreach ($recent as $r) {
                    $time = date('M j H:i', strtotime($r['timestamp']));
                    $parts[] = "  {$time}: {$r['bpm']} bpm ({$r['context']})";
                }
            }
        }

        // Irregular Rhythm Events (PVCs)
        if (! empty($data['irregular_rhythm_events'])) {
            $events = $data['irregular_rhythm_events'];
            $parts[] = '';
            $parts[] = 'IRREGULAR RHYTHM EVENTS (7d):';
            $parts[] = '  Total events: '.count($events);
            foreach ($events as $event) {
                $time = date('M j H:i', strtotime($event['timestamp']));
                $type = str_replace('_', ' ', $event['type']);
                $parts[] = "  {$time}: {$type}, {$event['duration_seconds']}s, HR {$event['heart_rate_at_event']} bpm";
            }
        }

        // HRV
        if (! empty($data['hrv'])) {
            $parts[] = '';
            $parts[] = 'HEART RATE VARIABILITY (HRV):';
            $parts[] = "  Average SDNN: {$data['hrv']['average_sdnn_ms']} ms";
            if (! empty($data['hrv']['daily_readings'])) {
                foreach (array_slice($data['hrv']['daily_readings'], 0, $dailyLimit) as $r) {
                    $parts[] = "  {$r['date']}: SDNN {$r['sdnn_ms']} ms, RMSSD {$r['rmssd_ms']} ms";
                }
            }
        }

        // Activity
        if (! empty($data['activity']['daily_steps'])) {
            $parts[] = '';
            $parts[] = 'DAILY ACTIVITY:';
            foreach (array_slice($data['activity']['daily_steps'], 0, $dailyLimit) as $day) {
                $parts[] = "  {$day['date']}: {$day['steps']} steps, {$day['distance_km']} km, {$day['active_minutes']} active min";
            }
        }

        // Sleep
        if (! empty($data['sleep'])) {
            $parts[] = '';
            $parts[] = 'SLEEP:';
            $parts[] = "  Average: {$data['sleep']['average_hours']} hours/night";
            if (! empty($data['sleep']['daily_readings'])) {
                foreach (array_slice($data['sleep']['daily_readings'], 0, $dailyLimit) as $day) {
                    $parts[] = "  {$day['date']}: {$day['total_hours']}h total (deep: {$day['deep_hours']}h, REM: {$day['rem_hours']}h, light: {$day['light_hours']}h)";
                }
            }
        }

        // Blood Oxygen
        if (! empty($data['blood_oxygen']['readings'])) {
            $parts[] = '';
            $parts[] = 'BLOOD OXYGEN (SpO2):';
            foreach (array_slice($data['blood_oxygen']['readings'], 0, $dailyLimit) as $r) {
                $date = date('M j', strtotime($r['timestamp']));
                $parts[] = "  {$date}: {$r['spo2_percent']}%";
            }
        }

        $parts[] = '--- END WEARABLE DEVICE DATA ---';

        return implode("\n", $parts);
    }

    /**
     * Load historical context summaries for a patient.
     * Only loads when context compaction is enabled via config.
     *
     * @return string|null Formatted context string, or null if disabled/empty
     */
    private function formatContextCompactionLayer(Visit $visit): ?string
    {
        if (! config('postvisit.context_compaction_enabled', false)) {
            return null;
        }

        $patient = $visit->patient;
        if (! $patient) {
            return null;
        }

        $summaries = PatientContextSummary::where('patient_id', $patient->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        if ($summaries->isEmpty()) {
            return null;
        }

        $parts = ['--- PREVIOUS SESSION CONTEXT ---'];
        $parts[] = 'The following summaries are from previous chat sessions with this patient:';

        foreach ($summaries as $summary) {
            $date = $summary->created_at?->format('M j, Y') ?? 'Unknown date';
            $parts[] = '';
            $parts[] = "Session ({$date}):";
            $parts[] = $summary->summary_text;

            if (! empty($summary->key_questions)) {
                $parts[] = 'Key questions asked: '.implode('; ', $summary->key_questions);
            }

            if (! empty($summary->concerns_raised)) {
                $parts[] = 'Concerns raised: '.implode('; ', $summary->concerns_raised);
            }

            if (! empty($summary->followup_items)) {
                $parts[] = 'Follow-up items: '.implode('; ', $summary->followup_items);
            }

            if ($summary->emotional_context) {
                $parts[] = "Emotional context: {$summary->emotional_context}";
            }
        }

        $parts[] = '--- END PREVIOUS SESSION CONTEXT ---';

        return implode("\n", $parts);
    }

    private function formatLibraryContext(Visit $visit): ?string
    {
        $patient = $visit->patient;
        if (! $patient || ! $patient->user) {
            return null;
        }

        $items = $patient->user->libraryItems()
            ->where('processing_status', 'completed')
            ->whereNotNull('ai_analysis')
            ->latest()
            ->limit(5)
            ->get();

        if ($items->isEmpty()) {
            return null;
        }

        $parts = ['--- PERSONAL MEDICAL LIBRARY ---'];
        $parts[] = 'The patient has uploaded the following medical documents for personal reference:';

        foreach ($items as $item) {
            $analysis = $item->ai_analysis;
            $parts[] = '';
            $parts[] = 'Document: '.($analysis['title'] ?? $item->title);
            $parts[] = 'Source: '.($item->source_type === 'pdf_upload' ? 'PDF upload' : 'Web article');

            if (! empty($analysis['categories']['evidence_level'])) {
                $parts[] = 'Evidence Level: '.$analysis['categories']['evidence_level']
                    .' ('.($analysis['categories']['evidence_description'] ?? '').')';
            }

            if (! empty($analysis['categories']['medical_topics'])) {
                $parts[] = 'Topics: '.implode(', ', $analysis['categories']['medical_topics']);
            }

            if (! empty($analysis['summary'])) {
                $parts[] = 'Summary: '.$analysis['summary'];
            }

            if (! empty($analysis['key_findings'])) {
                $parts[] = 'Key Findings:';
                foreach (array_slice($analysis['key_findings'], 0, 5) as $finding) {
                    $parts[] = '- '.$finding;
                }
            }

            if (! empty($analysis['patient_relevance']['relevance_explanation'])) {
                $parts[] = 'Relevance to Patient: '.$analysis['patient_relevance']['relevance_explanation'];
            }

            if (! empty($analysis['patient_relevance']['actionable_insights'])) {
                $parts[] = 'Actionable Insights:';
                foreach (array_slice($analysis['patient_relevance']['actionable_insights'], 0, 3) as $insight) {
                    $parts[] = '- '.$insight;
                }
            }
        }

        $parts[] = '--- END PERSONAL MEDICAL LIBRARY ---';

        return implode("\n", $parts);
    }
}
