<?php

namespace Database\Factories;

use App\Models\Medication;
use Illuminate\Database\Eloquent\Factories\Factory;

class MedicationFactory extends Factory
{
    protected $model = Medication::class;

    public function definition(): array
    {
        return [
            'rxnorm_code' => fake()->unique()->numerify('######'),
            'generic_name' => fake()->word().'ol',
            'display_name' => fake()->word().'ol '.fake()->randomElement(['10mg', '20mg', '40mg']),
            'form' => fake()->randomElement(['tablet', 'capsule', 'solution']),
            'strength_value' => fake()->randomElement([10, 20, 40, 100]),
            'strength_unit' => 'mg',
            'source' => 'rxnorm',
            'is_active' => true,
            'black_box_warning' => false,
        ];
    }
}
