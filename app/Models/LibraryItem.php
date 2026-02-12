<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LibraryItem extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id', 'title', 'source_type', 'source_url',
        'file_path', 'file_size', 'file_hash', 'content_type',
        'content_text', 'ai_analysis',
        'processing_status', 'processing_error', 'processed_at',
        'is_personal_use_only', 'copyright_notice',
    ];

    protected $hidden = ['content_text'];

    protected function casts(): array
    {
        return [
            'ai_analysis' => 'array',
            'processed_at' => 'datetime',
            'is_personal_use_only' => 'boolean',
            'file_size' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isCompleted(): bool
    {
        return $this->processing_status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->processing_status === 'failed';
    }
}
