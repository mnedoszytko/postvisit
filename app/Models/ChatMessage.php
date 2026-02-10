<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'session_id',
        'sender_type',
        'message_text',
        'referenced_entities',
        'extracted_entities',
        'ai_model_used',
        'ai_prompt_tokens',
        'ai_completion_tokens',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'referenced_entities' => 'array',
            'extracted_entities' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ChatSession::class, 'session_id');
    }
}
