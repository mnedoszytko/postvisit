<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'fhir_document_reference_id',
        'patient_id',
        'visit_id',
        'title',
        'description',
        'document_type',
        'content_type',
        'file_path',
        'file_size',
        'file_hash',
        'status',
        'document_date',
        'confidentiality_level',
        'created_by',
        'retention_until',
    ];

    protected function casts(): array
    {
        return [
            'document_date' => 'date',
            'retention_until' => 'date',
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
