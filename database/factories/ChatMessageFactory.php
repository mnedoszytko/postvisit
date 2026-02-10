<?php

namespace Database\Factories;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChatMessageFactory extends Factory
{
    protected $model = ChatMessage::class;

    public function definition(): array
    {
        return [
            'session_id' => ChatSession::factory(),
            'sender_type' => fake()->randomElement(['patient', 'ai']),
            'message_text' => fake()->paragraph(),
            'created_at' => now(),
        ];
    }
}
