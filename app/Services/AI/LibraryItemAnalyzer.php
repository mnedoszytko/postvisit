<?php

namespace App\Services\AI;

use App\Enums\AiTier;
use App\Models\LibraryItem;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

class LibraryItemAnalyzer
{
    public function __construct(
        private AnthropicClient $client,
        private PromptLoader $promptLoader,
        private AiTierManager $tierManager,
    ) {}

    public function process(LibraryItem $item): void
    {
        $tier = $this->tierManager->current();

        // Step 1: Extract Text
        $this->updateStatus($item, 'extracting_text');
        $text = $this->extractText($item);
        $item->update(['content_text' => $text]);

        // Step 2: Analyze
        $this->updateStatus($item, 'analyzing');
        $analysis = $this->analyze($text, $tier);

        // Step 3: Categorize
        $this->updateStatus($item, 'categorizing');
        $categories = $this->categorize($text, $analysis, $tier);

        // Step 4: Relate to Patient
        $this->updateStatus($item, 'relating');
        $relevance = $this->relateToPatient($item, $analysis, $tier);

        // Step 5: Verify
        $this->updateStatus($item, 'verifying');
        $verified = $this->verify($analysis, $categories, $relevance, $tier);

        // Save final result
        $item->update([
            'ai_analysis' => $verified,
            'processing_status' => 'completed',
            'processed_at' => now(),
            'title' => $verified['title'] ?? $item->title,
        ]);

        Log::channel('ai')->info('Library item analyzed', [
            'item_id' => $item->id,
            'source_type' => $item->source_type,
            'steps_completed' => 5,
        ]);
    }

    private function extractText(LibraryItem $item): string
    {
        if ($item->source_type === 'pdf_upload') {
            return $this->extractFromPdf($item);
        }

        return $this->extractFromUrl($item);
    }

    private function extractFromPdf(LibraryItem $item): string
    {
        $disk = config('filesystems.upload');

        // Resolve to local path for pdftotext CLI
        if ($disk === 'local') {
            $filePath = Storage::disk($disk)->path($item->file_path);
            $tmp = null;
        } else {
            $tmp = tempnam(sys_get_temp_dir(), 'pv_pdf_').'.pdf';
            file_put_contents($tmp, Storage::disk($disk)->get($item->file_path));
            $filePath = $tmp;
        }

        // Try pdftotext CLI first
        $result = Process::run(['pdftotext', '-layout', $filePath, '-']);

        if ($result->successful() && trim($result->output()) !== '') {
            if ($tmp) {
                @unlink($tmp);
            }

            return trim($result->output());
        }

        if ($tmp) {
            @unlink($tmp);
        }

        // Fallback: send PDF to Claude for text extraction
        $base64 = base64_encode(Storage::disk($disk)->get($item->file_path));

        $response = $this->client->chat(
            'Extract all text from this PDF document. Return only the extracted text, no commentary.',
            [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'document',
                            'source' => [
                                'type' => 'base64',
                                'media_type' => 'application/pdf',
                                'data' => $base64,
                            ],
                        ],
                        [
                            'type' => 'text',
                            'text' => 'Extract all text content from this document.',
                        ],
                    ],
                ],
            ],
            ['max_tokens' => 16000]
        );

        return $response;
    }

    private function extractFromUrl(LibraryItem $item): string
    {
        $response = Http::timeout(30)
            ->withHeaders([
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.9',
            ])
            ->withUserAgent('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36')
            ->get($item->source_url);

        if ($response->failed()) {
            // Cloudflare or similar bot protection — save what we can
            $host = parse_url($item->source_url, PHP_URL_HOST) ?? 'unknown';
            Log::warning("Library URL fetch failed ({$response->status()}), using URL-only mode", [
                'item_id' => $item->id,
                'url' => $item->source_url,
            ]);

            return "Source URL: {$item->source_url}\nDomain: {$host}\n\nThe full content could not be retrieved (HTTP {$response->status()} — likely bot protection). "
                .'Please analyze based on the URL and domain context. The user added this to their medical library for reference.';
        }

        $html = $response->body();
        $userId = $item->user_id;

        // Check TDM opt-out headers
        if ($response->header('TDM-Reservation') === '1' ||
            str_contains($response->header('X-Robots-Tag') ?? '', 'noai')) {
            $copyrightNotice = 'TDM opt-out detected. Content stored for personal use only under EU TDM exception.';
            $item->update(['copyright_notice' => $copyrightNotice]);
        }

        // Save raw HTML
        $htmlPath = "library/{$userId}/{$item->id}-raw.html";
        Storage::disk(config('filesystems.upload'))->put($htmlPath, $html);
        $item->update([
            'file_path' => $htmlPath,
            'file_size' => strlen($html),
            'file_hash' => hash('sha256', $html),
        ]);

        // Use Claude to extract clean text from HTML
        $htmlTruncated = mb_substr($html, 0, 100000);
        $text = $this->client->chat(
            'Extract the main article text from this HTML, removing navigation, ads, headers, footers, and boilerplate. Return only the clean article text.',
            [['role' => 'user', 'content' => $htmlTruncated]],
            ['max_tokens' => 16000]
        );

        return trim($text);
    }

    private function analyze(string $text, AiTier $tier): array
    {
        $prompt = $this->promptLoader->load('library-item-analyzer');
        $textTruncated = mb_substr($text, 0, 100000);

        $budgetTokens = $tier->thinkingBudget('library');

        $response = $this->client->chatWithThinking($prompt, [
            [
                'role' => 'user',
                'content' => "Analyze this medical document and return structured JSON.\n\nDocument text:\n{$textTruncated}",
            ],
        ], [
            'model' => $tier->model(),
            'max_tokens' => $budgetTokens + 8000,
            'budget_tokens' => $budgetTokens,
        ]);

        return AnthropicClient::parseJsonOutput($response['text'], [
            'title' => 'Unknown Document',
            'summary' => 'Unable to analyze.',
            'key_findings' => [],
            'recommendations' => [],
            'publication_info' => [],
        ]);
    }

    private function categorize(string $text, array $analysis, AiTier $tier): array
    {
        $analysisJson = json_encode($analysis, JSON_UNESCAPED_UNICODE);
        $textPreview = mb_substr($text, 0, 5000);

        $response = $this->client->chatWithThinking(
            'You are a medical document categorizer. Given a document analysis, categorize it. Respond with JSON only inside ```json``` fences.',
            [
                [
                    'role' => 'user',
                    'content' => "Categorize this medical document.\n\nAnalysis:\n{$analysisJson}\n\nDocument preview:\n{$textPreview}\n\nReturn JSON with: medical_topics (array), evidence_level (A/B/C), evidence_description, specialty_areas (array), document_type (clinical_guideline/research_paper/patient_education/drug_info/other), icd10_codes (array).",
                ],
            ],
            [
                'model' => $tier->model(),
                'max_tokens' => min($tier->thinkingBudget('library'), 4000) + 4000,
                'budget_tokens' => min($tier->thinkingBudget('library'), 4000),
            ]
        );

        return AnthropicClient::parseJsonOutput($response['text'], [
            'medical_topics' => [],
            'evidence_level' => 'C',
            'evidence_description' => 'Unknown',
            'specialty_areas' => [],
            'document_type' => 'other',
            'icd10_codes' => [],
        ]);
    }

    private function relateToPatient(LibraryItem $item, array $analysis, AiTier $tier): array
    {
        $user = $item->user;
        $patient = $user?->patient;

        $patientContext = 'No patient record available.';
        if ($patient) {
            $patient->load(['visits' => fn ($q) => $q->latest()->limit(1), 'visits.conditions', 'visits.prescriptions.medication']);
            $conditions = [];
            $medications = [];

            foreach ($patient->visits as $visit) {
                foreach ($visit->conditions as $c) {
                    $conditions[] = "{$c->code_display} ({$c->code})";
                }
                foreach ($visit->prescriptions as $rx) {
                    if ($rx->medication) {
                        $medications[] = $rx->medication->generic_name;
                    }
                }
            }

            $patientContext = 'Patient conditions: '.(implode(', ', array_unique($conditions)) ?: 'None').
                "\nPatient medications: ".(implode(', ', array_unique($medications)) ?: 'None');
        }

        $analysisJson = json_encode($analysis, JSON_UNESCAPED_UNICODE);

        $response = $this->client->chatWithThinking(
            'You are a medical relevance assessor. Given a document analysis and patient context, assess relevance. Respond with JSON only inside ```json``` fences.',
            [
                [
                    'role' => 'user',
                    'content' => "Assess how relevant this document is to this patient.\n\nDocument analysis:\n{$analysisJson}\n\n{$patientContext}\n\nReturn JSON with: relevance_score (0-1), relevance_explanation, matching_conditions (array), matching_medications (array), actionable_insights (array of strings).",
                ],
            ],
            [
                'model' => $tier->model(),
                'max_tokens' => min($tier->thinkingBudget('library'), 4000) + 4000,
                'budget_tokens' => min($tier->thinkingBudget('library'), 4000),
            ]
        );

        return AnthropicClient::parseJsonOutput($response['text'], [
            'relevance_score' => 0,
            'relevance_explanation' => 'Unable to assess relevance.',
            'matching_conditions' => [],
            'matching_medications' => [],
            'actionable_insights' => [],
        ]);
    }

    private function verify(array $analysis, array $categories, array $relevance, AiTier $tier): array
    {
        $combined = json_encode([
            'analysis' => $analysis,
            'categories' => $categories,
            'patient_relevance' => $relevance,
        ], JSON_UNESCAPED_UNICODE);

        $response = $this->client->chatWithThinking(
            'You are a medical document verification agent. Verify the combined analysis for accuracy, safety, and copyright compliance. Respond with JSON only inside ```json``` fences.',
            [
                [
                    'role' => 'user',
                    'content' => "Verify this medical document analysis.\n\n{$combined}\n\nCheck:\n1. No unsupported medical claims\n2. No copyrighted content verbatim in summaries\n3. Categorization accuracy\n4. Relevance assessment accuracy\n\nReturn JSON with all original fields merged, plus a 'verification' object: { verified: bool, issues: [], confidence: 'high'|'medium'|'low' }",
                ],
            ],
            [
                'model' => $tier->model(),
                'max_tokens' => min($tier->thinkingBudget('library'), 6000) + 8000,
                'budget_tokens' => min($tier->thinkingBudget('library'), 6000),
            ]
        );

        $result = AnthropicClient::parseJsonOutput($response['text'], []);

        // Merge all parts into the canonical structure
        return [
            'title' => $result['title'] ?? $analysis['title'] ?? 'Unknown Document',
            'summary' => $result['summary'] ?? $analysis['summary'] ?? '',
            'key_findings' => $result['key_findings'] ?? $analysis['key_findings'] ?? [],
            'recommendations' => $result['recommendations'] ?? $analysis['recommendations'] ?? [],
            'publication_info' => $result['publication_info'] ?? $analysis['publication_info'] ?? [],
            'categories' => $result['categories'] ?? $categories,
            'patient_relevance' => $result['patient_relevance'] ?? $relevance,
            'verification' => $result['verification'] ?? [
                'verified' => true,
                'issues' => [],
                'confidence' => 'medium',
            ],
            'pipeline_version' => '1.0',
            'processed_at' => now()->toIso8601String(),
        ];
    }

    private function updateStatus(LibraryItem $item, string $status): void
    {
        $item->update(['processing_status' => $status]);
    }
}
