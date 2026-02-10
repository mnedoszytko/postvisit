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
            'fhir_document_reference_id' => 'DocumentReference/' . Str::uuid(),
            'patient_id' => Patient::factory(),
            'visit_id' => Visit::factory(),
            'title' => fake()->sentence(3),
            'document_type' => fake()->randomElement(['lab_report', 'discharge_summary', 'progress_note']),
            'content_type' => 'pdf',
            'file_path' => 'documents/' . Str::uuid() . '.pdf',
            'file_size' => fake()->numberBetween(1024, 1048576),
            'file_hash' => md5(Str::uuid()),
            'status' => 'current',
            'document_date' => now()->toDateString(),
            'confidentiality_level' => 'M',
        ];
    }
}
