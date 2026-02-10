<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Medication extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'rxnorm_code',
        'atc_code',
        'ndc_code',
        'generic_name',
        'brand_names',
        'display_name',
        'form',
        'strength_value',
        'strength_unit',
        'ingredients',
        'black_box_warning',
        'pregnancy_category',
        'source',
        'source_last_updated',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'brand_names' => 'array',
            'ingredients' => 'array',
            'black_box_warning' => 'boolean',
            'is_active' => 'boolean',
            'strength_value' => 'decimal:4',
            'source_last_updated' => 'datetime',
        ];
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    public function interactionsAsDrugA(): HasMany
    {
        return $this->hasMany(MedicationInteraction::class, 'drug_a_id');
    }

    public function interactionsAsDrugB(): HasMany
    {
        return $this->hasMany(MedicationInteraction::class, 'drug_b_id');
    }
}
