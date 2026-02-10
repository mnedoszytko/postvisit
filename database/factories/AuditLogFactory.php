<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'user_role' => fake()->randomElement(['patient', 'doctor', 'admin']),
            'action_type' => fake()->randomElement(['create', 'read', 'update']),
            'resource_type' => fake()->randomElement(['visit', 'patient', 'prescription']),
            'resource_id' => Str::uuid(),
            'success' => true,
            'ip_address' => fake()->ipv4(),
            'session_id' => Str::uuid(),
            'phi_accessed' => false,
            'accessed_at' => now(),
        ];
    }
}
