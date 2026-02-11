<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UploadToken extends Model
{
    use HasUuids;

    protected $fillable = [
        'token',
        'visit_id',
        'created_by',
        'expires_at',
        'used_at',
        'document_id',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function scopeValid($query)
    {
        return $query->where('expires_at', '>', now())
            ->whereNull('used_at');
    }

    public function isValid(): bool
    {
        return $this->expires_at->isFuture() && $this->used_at === null;
    }

    public function markUsed(Document $document): void
    {
        $this->update([
            'used_at' => now(),
            'document_id' => $document->id,
        ]);
    }
}
