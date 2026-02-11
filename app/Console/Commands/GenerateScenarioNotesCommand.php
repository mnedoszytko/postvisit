<?php

namespace App\Console\Commands;

use App\Services\AI\AnthropicClient;
use App\Services\AI\PromptLoader;
use Illuminate\Console\Command;

class GenerateScenarioNotesCommand extends Command
{
    protected $signature = 'app:generate-scenario-notes
                            {scenarios?* : Specific scenario keys to process (default: all source_dir scenarios)}
                            {--force : Overwrite existing soap-note.json files}';

    protected $description = 'Generate SOAP notes and medical terms for demo scenarios using Claude Opus';

    public function __construct(
        private AnthropicClient $client,
        private PromptLoader $promptLoader,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $allScenarios = config('demo-scenarios.scenarios');
        $requestedKeys = $this->argument('scenarios');

        // Filter to scenarios that have a transcript to process (source_dir or notes_dir or transcript_file)
        $scenarios = collect($allScenarios)
            ->filter(fn (array $s) => isset($s['source_dir']) || isset($s['notes_dir']) || isset($s['transcript_file']))
            ->when(! empty($requestedKeys), fn ($c) => $c->filter(
                fn ($s, $key) => in_array($key, $requestedKeys)
            ));

        if ($scenarios->isEmpty()) {
            $this->warn('No matching scenarios with transcripts found.');

            return self::FAILURE;
        }

        $this->info("Processing {$scenarios->count()} scenario(s) via Claude Opus...\n");

        $processed = 0;
        $failed = 0;

        foreach ($scenarios as $key => $scenario) {
            $outputDir = base_path($scenario['source_dir'] ?? $scenario['notes_dir'] ?? dirname($scenario['transcript_file']));
            $transcriptPath = isset($scenario['transcript_file'])
                ? base_path($scenario['transcript_file'])
                : $outputDir.'/raw-transcript.txt';
            $soapOutputPath = $outputDir.'/soap-note.json';
            $termsOutputPath = $outputDir.'/medical-terms.json';

            $this->components->twoColumnDetail($key, $scenario['name']);

            // Skip if already generated (unless --force)
            if (file_exists($soapOutputPath) && ! $this->option('force')) {
                $this->line('  <comment>Skipped</comment> — soap-note.json already exists (use --force to regenerate)');

                continue;
            }

            if (! file_exists($transcriptPath)) {
                $this->line('  <error>Missing</error> — raw-transcript.txt not found');
                $failed++;

                continue;
            }

            $transcript = file_get_contents($transcriptPath);
            $wordCount = str_word_count($transcript);
            $this->line("  Transcript: {$wordCount} words");

            // Step 1: Generate SOAP note
            $this->line('  Generating SOAP note...');
            $startTime = microtime(true);

            try {
                $soapResult = $this->generateSoapNote($transcript, $scenario);
            } catch (\Throwable $e) {
                $this->line("  <error>SOAP failed</error>: {$e->getMessage()}");
                $failed++;

                continue;
            }

            $elapsed = round(microtime(true) - $startTime, 1);
            $this->line("  SOAP note generated in {$elapsed}s");

            // Save SOAP note
            file_put_contents($soapOutputPath, json_encode($soapResult, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->line('  Saved: soap-note.json');

            // Step 2: Generate medical terms
            $soapNote = $soapResult['soap_note'] ?? [];
            if (! empty($soapNote)) {
                $this->line('  Extracting medical terms...');
                $startTime = microtime(true);

                try {
                    $terms = $this->extractMedicalTerms($soapNote);
                    $elapsed = round(microtime(true) - $startTime, 1);
                    $termCount = collect($terms)->sum(fn ($section) => is_array($section) ? count($section) : 0);
                    $this->line("  Extracted {$termCount} terms in {$elapsed}s");

                    file_put_contents($termsOutputPath, json_encode($terms, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                    $this->line('  Saved: medical-terms.json');
                } catch (\Throwable $e) {
                    $this->line("  <comment>Term extraction failed (non-fatal)</comment>: {$e->getMessage()}");
                }
            }

            $processed++;
            $this->newLine();
        }

        $this->newLine();
        $this->components->info("Done: {$processed} processed, {$failed} failed");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Generate SOAP note from transcript using the same prompt as ScribeProcessor.
     *
     * @return array{clean_transcript: string, extracted_entities: array, soap_note: array, speakers: array}
     */
    private function generateSoapNote(string $transcript, array $scenario): array
    {
        $systemPrompt = $this->promptLoader->load('scribe-processor');

        $metadata = [
            'specialty' => 'cardiology',
            'visit_date' => now()->subDays($scenario['visit']['days_ago'] ?? 1)->toDateString(),
            'practitioner' => config('demo-scenarios.doctor.name', 'Dr. Nedoszytko'),
        ];

        $messages = [
            [
                'role' => 'user',
                'content' => "Process the following visit transcript.\n\n".
                    "Visit Metadata:\n".json_encode($metadata, JSON_PRETTY_PRINT)."\n\n".
                    "Raw Transcript:\n".$transcript,
            ],
        ];

        $response = $this->client->chatWithThinking($systemPrompt, $messages, [
            'model' => 'claude-opus-4-6',
            'max_tokens' => 16000,
            'budget_tokens' => 10000,
        ]);

        return $this->parseJsonResponse($response['text']);
    }

    /**
     * Extract medical terms from SOAP note sections using the same prompt as TermExtractor.
     *
     * @param  array<string, string>  $soapNote
     * @return array<string, array<array{term: string, start: int, end: int, definition: string}>>
     */
    private function extractMedicalTerms(array $soapNote): array
    {
        $systemPrompt = $this->promptLoader->load('term-extractor');

        $sections = [
            'chief_complaint',
            'history_of_present_illness',
            'review_of_systems',
            'physical_exam',
            'assessment',
            'plan',
            'follow_up',
        ];

        $parts = [];
        foreach ($sections as $section) {
            $text = $soapNote[$section] ?? null;
            if ($text) {
                $parts[] = "=== SECTION: {$section} ===\n{$text}";
            }
        }

        if (empty($parts)) {
            return [];
        }

        $messages = [
            [
                'role' => 'user',
                'content' => implode("\n\n", $parts),
            ],
        ];

        $response = $this->client->chat($systemPrompt, $messages, [
            'model' => 'claude-opus-4-6',
            'max_tokens' => 8192,
        ]);

        $terms = $this->parseJsonResponse($response);

        // Validate offsets against actual text
        return $this->validateTermOffsets($soapNote, $terms);
    }

    /**
     * Validate that extracted term offsets match the actual SOAP text.
     *
     * @param  array<string, string>  $soapNote
     * @param  array<string, array>  $terms
     * @return array<string, array>
     */
    private function validateTermOffsets(array $soapNote, array $terms): array
    {
        $validated = [];

        foreach ($terms as $section => $sectionTerms) {
            $text = $soapNote[$section] ?? '';
            if (! $text || ! is_array($sectionTerms)) {
                continue;
            }

            $validTerms = [];
            foreach ($sectionTerms as $entry) {
                if (! isset($entry['term'], $entry['start'], $entry['end'])) {
                    continue;
                }

                $start = (int) $entry['start'];
                $end = (int) $entry['end'];
                $length = $end - $start;

                if ($length <= 0 || $start < 0 || $end > strlen($text)) {
                    // Try fallback search
                    $pos = stripos($text, $entry['term']);
                    if ($pos !== false) {
                        $start = $pos;
                        $end = $pos + strlen($entry['term']);
                    } else {
                        continue;
                    }
                }

                $actual = substr($text, $start, $end - $start);
                $matched = strtolower($actual) === strtolower($entry['term']);

                if (! $matched) {
                    $pos = stripos($text, $entry['term']);
                    if ($pos !== false) {
                        $start = $pos;
                        $end = $pos + strlen($entry['term']);
                        $actual = substr($text, $start, strlen($entry['term']));
                        $matched = true;
                    }
                }

                if ($matched) {
                    $validTerms[] = [
                        'term' => $actual,
                        'start' => $start,
                        'end' => $end,
                        'definition' => $entry['definition'] ?? '',
                    ];
                }
            }

            if ($validTerms) {
                $validated[$section] = $validTerms;
            }
        }

        return $validated;
    }

    private function parseJsonResponse(string $response): array
    {
        if (preg_match('/```(?:json)?\s*\n?(.*?)\n?```/s', $response, $matches)) {
            $response = $matches[1];
        }

        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'clean_transcript' => $response,
                'extracted_entities' => [],
                'soap_note' => [],
                'speakers' => [],
            ];
        }

        return $decoded;
    }
}
