<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientUpdateTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->patient = Patient::factory()->create();
        $this->user = User::factory()->patient($this->patient)->create();
    }

    public function test_can_update_health_profile(): void
    {
        $data = [
            'height_cm' => 180.3,
            'weight_kg' => 82.0,
            'blood_type' => 'A+',
            'allergies' => [
                ['name' => 'Penicillin', 'severity' => 'severe', 'reaction' => 'Anaphylaxis'],
            ],
            'emergency_contact_name' => 'Sarah Johnson',
            'emergency_contact_phone' => '+1-555-0124',
            'emergency_contact_relationship' => 'Spouse',
        ];

        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/patients/{$this->patient->id}", $data);

        $response->assertOk()
            ->assertJsonPath('data.height_cm', '180.3')
            ->assertJsonPath('data.weight_kg', '82.0')
            ->assertJsonPath('data.blood_type', 'A+')
            ->assertJsonPath('data.emergency_contact_name', 'Sarah Johnson')
            ->assertJsonPath('data.emergency_contact_phone', '+1-555-0124')
            ->assertJsonPath('data.emergency_contact_relationship', 'Spouse')
            ->assertJsonPath('data.allergies.0.name', 'Penicillin')
            ->assertJsonPath('data.allergies.0.severity', 'severe');
    }

    public function test_validates_height_out_of_range(): void
    {
        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/patients/{$this->patient->id}", [
                'height_cm' => 10,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['height_cm']);
    }

    public function test_validates_weight_out_of_range(): void
    {
        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/patients/{$this->patient->id}", [
                'weight_kg' => 600,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['weight_kg']);
    }

    public function test_validates_invalid_blood_type(): void
    {
        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/patients/{$this->patient->id}", [
                'blood_type' => 'X+',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['blood_type']);
    }

    public function test_validates_allergy_severity(): void
    {
        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/patients/{$this->patient->id}", [
                'allergies' => [
                    ['name' => 'Peanuts', 'severity' => 'extreme'],
                ],
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['allergies.0.severity']);
    }

    public function test_partial_update_only_changes_provided_fields(): void
    {
        $this->patient->update([
            'height_cm' => 175.0,
            'blood_type' => 'O-',
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/patients/{$this->patient->id}", [
                'weight_kg' => 85.5,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.weight_kg', '85.5')
            ->assertJsonPath('data.height_cm', '175.0')
            ->assertJsonPath('data.blood_type', 'O-');
    }

    public function test_allergies_array_format(): void
    {
        $allergies = [
            ['name' => 'Penicillin', 'severity' => 'severe', 'reaction' => 'Anaphylaxis'],
            ['name' => 'Sulfa drugs', 'severity' => 'moderate', 'reaction' => 'Skin rash'],
        ];

        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/patients/{$this->patient->id}", [
                'allergies' => $allergies,
            ]);

        $response->assertOk()
            ->assertJsonCount(2, 'data.allergies')
            ->assertJsonPath('data.allergies.0.name', 'Penicillin')
            ->assertJsonPath('data.allergies.1.name', 'Sulfa drugs');
    }

    public function test_update_requires_authentication(): void
    {
        $response = $this->patchJson("/api/v1/patients/{$this->patient->id}", [
            'height_cm' => 180.0,
        ]);

        $response->assertStatus(401);
    }
}
