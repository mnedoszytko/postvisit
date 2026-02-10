<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicationInteraction extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'drug_a_id',
        'drug_b_id',
        'severity',
        'description',
        'management',
        'source_database',
        'should_alert',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'should_alert' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function drugA(): BelongsTo
    {
        return $this->belongsTo(Medication::class, 'drug_a_id');
    }

    public function drugB(): BelongsTo
    {
        return $this->belongsTo(Medication::class, 'drug_b_id');
    }
}
