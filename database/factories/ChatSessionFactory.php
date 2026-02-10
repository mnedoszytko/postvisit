<?php

namespace Database\Factories;

use App\Models\ChatSession;
use App\Models\Patient;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChatSessionFactory extends Factory
{
    protected $model = ChatSession::class;

    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'visit_id' => Visit::factory(),
            'topic' => fake()->sentence(3),
            'status' => 'active',
            'initiated_at' => now(),
        ];
    }
}
