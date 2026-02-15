<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientContextSummary extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'patient_id',
        'visit_id',
        'session_id',
        'summary_text',
        'key_questions',
        'concerns_raised',
        'followup_items',
        'emotional_context',
        'token_count',
    ];

    protected function casts(): array
    {
        return [
            'key_questions' => 'array',
            'concerns_raised' => 'array',
            'followup_items' => 'array',
            'token_count' => 'integer',
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

    public function chatSession(): BelongsTo
    {
        return $this->belongsTo(ChatSession::class, 'session_id');
    }
}
