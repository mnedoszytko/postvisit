<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prescription extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'fhir_medication_request_id',
        'patient_id',
        'practitioner_id',
        'visit_id',
        'medication_id',
        'status',
        'intent',
        'dose_quantity',
        'dose_unit',
        'frequency',
        'frequency_text',
        'route',
        'start_date',
        'end_date',
        'duration_days',
        'number_of_refills',
        'refills_remaining',
        'special_instructions',
        'indication',
        'indication_code',
        'substitution_allowed',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'dose_quantity' => 'decimal:4',
            'start_date' => 'date',
            'end_date' => 'date',
            'substitution_allowed' => 'boolean',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(Practitioner::class);
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function medication(): BelongsTo
    {
        return $this->belongsTo(Medication::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
