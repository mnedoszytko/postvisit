<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Practitioner extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'fhir_practitioner_id',
        'first_name',
        'last_name',
        'email',
        'npi',
        'license_number',
        'medical_degree',
        'primary_specialty',
        'secondary_specialties',
        'organization_id',
    ];

    protected function casts(): array
    {
        return [
            'secondary_specialties' => 'array',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    public function visitNotes(): HasMany
    {
        return $this->hasMany(VisitNote::class, 'author_practitioner_id');
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'practitioner_id');
    }
}
