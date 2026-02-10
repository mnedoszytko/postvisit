<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'visit_id' => Visit::factory(),
            'type' => fake()->randomElement(['patient_feedback', 'escalation', 'doctor_reply']),
            'title' => fake()->sentence(4),
            'body' => fake()->paragraph(),
            'created_at' => now(),
        ];
    }
}
