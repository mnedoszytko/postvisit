<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_can_register_and_get_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Alex Johnson',
            'email' => 'alex@example.com',
            'password' => 'securepass123',
            'password_confirmation' => 'securepass123',
            'role' => 'patient',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.user.name', 'Alex Johnson')
            ->assertJsonPath('data.user.email', 'alex@example.com')
            ->assertJsonPath('data.user.role', 'patient')
            ->assertJsonStructure(['data' => ['token']]);

        $this->assertNotEmpty($response->json('data.token'));
    }

    public function test_doctor_can_register(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Dr. Sarah Chen',
            'email' => 'dr.chen@clinic.com',
            'password' => 'securepass123',
            'password_confirmation' => 'securepass123',
            'role' => 'doctor',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.user.role', 'doctor');

        $this->assertDatabaseHas('users', [
            'email' => 'dr.chen@clinic.com',
            'role' => 'doctor',
        ]);
    }

    public function test_registration_fails_with_password_mismatch(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'securepass123',
            'password_confirmation' => 'differentpass',
            'role' => 'patient',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('password');
    }

    public function test_registration_fails_without_name(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'email' => 'test@example.com',
            'password' => 'securepass123',
            'password_confirmation' => 'securepass123',
            'role' => 'patient',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_registration_fails_with_invalid_email(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'not-an-email',
            'password' => 'securepass123',
            'password_confirmation' => 'securepass123',
            'role' => 'patient',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_registration_fails_with_short_password(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
            'role' => 'patient',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('password');
    }

    public function test_registered_user_can_immediately_authenticate(): void
    {
        $registerResponse = $this->postJson('/api/v1/auth/register', [
            'name' => 'New Patient',
            'email' => 'new@example.com',
            'password' => 'securepass123',
            'password_confirmation' => 'securepass123',
            'role' => 'patient',
        ]);

        $token = $registerResponse->json('data.token');

        $profileResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/auth/user');

        $profileResponse->assertOk()
            ->assertJsonPath('data.email', 'new@example.com');
    }
}
