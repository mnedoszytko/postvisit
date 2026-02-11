<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitNote extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'visit_id',
        'patient_id',
        'author_practitioner_id',
        'composition_type',
        'status',
        'chief_complaint',
        'history_of_present_illness',
        'review_of_systems',
        'physical_exam',
        'assessment',
        'assessment_codes',
        'plan',
        'follow_up',
        'follow_up_timeframe',
        'additional_sections',
        'medical_terms',
        'is_signed',
        'signed_at',
    ];

    protected function casts(): array
    {
        return [
            'assessment_codes' => 'array',
            'additional_sections' => 'array',
            'medical_terms' => 'array',
            'is_signed' => 'boolean',
            'signed_at' => 'datetime',
        ];
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function authorPractitioner(): BelongsTo
    {
        return $this->belongsTo(Practitioner::class, 'author_practitioner_id');
    }
}
