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

    protected $appends = ['photo_url'];

    protected function casts(): array
    {
        return [
            'secondary_specialties' => 'array',
        ];
    }

    /**
     * Map specialty to demo doctor photo directory key.
     */
    public function getPhotoUrlAttribute(): ?string
    {
        $specialtyMap = [
            'cardiology' => 'default',
            'endocrinology' => 'endocrinologist',
            'gastroenterology' => 'gastroenterologist',
            'pulmonology' => 'pulmonologist',
        ];

        $key = $specialtyMap[$this->primary_specialty] ?? 'default';
        $path = base_path("demo/doctors/{$key}/doctor-photo.png");

        if (! file_exists($path)) {
            return null;
        }

        return "/api/v1/demo/doctors/{$key}/photo";
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
