<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'type',
        'address',
        'phone',
        'email',
    ];

    public function practitioners(): HasMany
    {
        return $this->hasMany(Practitioner::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }
}
