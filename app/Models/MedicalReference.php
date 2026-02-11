<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class MedicalReference extends Model
{
    use HasUuids;

    protected $fillable = [
        'title',
        'authors',
        'journal',
        'year',
        'doi',
        'pmid',
        'url',
        'source_organization',
        'category',
        'specialty',
        'summary',
        'verified',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'verified' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }

    public function scopeVerified($query)
    {
        return $query->where('verified', true);
    }

    public function scopeBySpecialty($query, string $specialty)
    {
        return $query->where('specialty', $specialty);
    }

    public function scopeGuidelines($query)
    {
        return $query->where('category', 'guideline');
    }
}
