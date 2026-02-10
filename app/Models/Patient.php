<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'fhir_patient_id',
        'first_name',
        'last_name',
        'dob',
        'gender',
        'email',
        'phone',
        'preferred_language',
        'timezone',
        'mrn',
        'ssn_encrypted',
        'consent_given',
        'consent_date',
        'data_sharing_consent',
        'right_to_erasure_requested',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'dob' => 'date',
            'consent_given' => 'boolean',
            'consent_date' => 'datetime',
            'data_sharing_consent' => 'boolean',
            'right_to_erasure_requested' => 'boolean',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    public function conditions(): HasMany
    {
        return $this->hasMany(Condition::class);
    }

    public function observations(): HasMany
    {
        return $this->hasMany(Observation::class);
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function chatSessions(): HasMany
    {
        return $this->hasMany(ChatSession::class);
    }

    public function consents(): HasMany
    {
        return $this->hasMany(Consent::class);
    }

    public function transcripts(): HasMany
    {
        return $this->hasMany(Transcript::class);
    }

    public function visitNotes(): HasMany
    {
        return $this->hasMany(VisitNote::class);
    }
}
