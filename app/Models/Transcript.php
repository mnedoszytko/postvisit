<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transcript extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'visit_id',
        'patient_id',
        'source_type',
        'stt_provider',
        'audio_duration_seconds',
        'audio_file_path',
        'raw_transcript',
        'diarized_transcript',
        'entities_extracted',
        'soap_note',
        'summary',
        'processing_status',
        'patient_consent_given',
        'consent_timestamp',
    ];

    protected function casts(): array
    {
        return [
            'raw_transcript' => 'encrypted',
            'summary' => 'encrypted',
            'diarized_transcript' => 'encrypted:array',
            'entities_extracted' => 'encrypted:array',
            'soap_note' => 'encrypted:array',
            'patient_consent_given' => 'boolean',
            'consent_timestamp' => 'datetime',
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
}
