<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'user_role',
        'action_type',
        'resource_type',
        'resource_id',
        'success',
        'ip_address',
        'session_id',
        'phi_accessed',
        'phi_elements',
        'accessed_at',
    ];

    protected function casts(): array
    {
        return [
            'success' => 'boolean',
            'phi_accessed' => 'boolean',
            'phi_elements' => 'array',
            'accessed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
