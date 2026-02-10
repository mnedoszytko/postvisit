<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Observation extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'fhir_observation_id',
        'patient_id',
        'visit_id',
        'practitioner_id',
        'code_system',
        'code',
        'code_display',
        'category',
        'status',
        'value_type',
        'value_quantity',
        'value_unit',
        'value_string',
        'value_boolean',
        'reference_range_low',
        'reference_range_high',
        'reference_range_text',
        'interpretation',
        'effective_date',
        'issued_at',
        'specialty_data',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'value_quantity' => 'decimal:4',
            'value_boolean' => 'boolean',
            'reference_range_low' => 'decimal:4',
            'reference_range_high' => 'decimal:4',
            'effective_date' => 'date',
            'issued_at' => 'datetime',
            'specialty_data' => 'array',
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

    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(Practitioner::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
