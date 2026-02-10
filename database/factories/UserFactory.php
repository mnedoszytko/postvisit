<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\Practitioner;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => 'patient',
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function patient(?Patient $patient = null): static
    {
        return $this->state(fn () => [
            'role' => 'patient',
            'patient_id' => $patient?->id ?? Patient::factory(),
        ]);
    }

    public function doctor(?Practitioner $practitioner = null): static
    {
        return $this->state(fn () => [
            'role' => 'doctor',
            'practitioner_id' => $practitioner?->id ?? Practitioner::factory(),
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn () => [
            'role' => 'admin',
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn () => [
            'email_verified_at' => null,
        ]);
    }
}
