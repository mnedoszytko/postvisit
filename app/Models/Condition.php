<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Condition extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'fhir_condition_id',
        'patient_id',
        'visit_id',
        'code_system',
        'code',
        'code_display',
        'category',
        'clinical_status',
        'verification_status',
        'severity',
        'onset_date',
        'abatement_date',
        'clinical_notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'onset_date' => 'date',
            'abatement_date' => 'date',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
