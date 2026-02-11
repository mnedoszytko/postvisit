<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\Patient;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        return [
            'fhir_document_reference_id' => 'DocumentReference/'.Str::uuid(),
            'patient_id' => Patient::factory(),
            'visit_id' => Visit::factory(),
            'title' => fake()->sentence(3),
            'document_type' => fake()->randomElement(['lab_report', 'discharge_summary', 'progress_note']),
            'content_type' => 'pdf',
            'file_path' => 'documents/'.Str::uuid().'.pdf',
            'file_size' => fake()->numberBetween(1024, 1048576),
            'file_hash' => md5(Str::uuid()),
            'analysis_status' => 'pending',
            'status' => 'current',
            'document_date' => now()->toDateString(),
            'confidentiality_level' => 'M',
        ];
    }

    public function analyzed(): static
    {
        return $this->state(fn () => [
            'analysis_status' => 'completed',
            'analyzed_at' => now(),
            'ai_analysis' => [
                'summary' => 'Normal sinus rhythm with occasional premature ventricular complexes (PVCs).',
                'findings' => [
                    [
                        'finding' => 'Occasional PVCs',
                        'location' => 'Leads II, V1-V3',
                        'significance' => 'moderate',
                    ],
                    [
                        'finding' => 'Normal sinus rhythm',
                        'location' => 'All leads',
                        'significance' => 'normal',
                    ],
                ],
                'key_values' => [
                    [
                        'label' => 'Heart Rate',
                        'value' => '72',
                        'unit' => 'bpm',
                        'reference_range' => '60-100',
                        'status' => 'normal',
                    ],
                    [
                        'label' => 'PR Interval',
                        'value' => '160',
                        'unit' => 'ms',
                        'reference_range' => '120-200',
                        'status' => 'normal',
                    ],
                ],
                'confidence' => 'high',
                'document_category' => 'ecg',
                'safety_note' => 'This is an AI-generated analysis for informational purposes only. It does not constitute a medical diagnosis. Always consult your healthcare provider for clinical interpretation.',
            ],
        ]);
    }
}
