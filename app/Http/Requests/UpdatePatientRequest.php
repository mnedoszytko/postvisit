<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<string>>
     */
    public function rules(): array
    {
        return [
            'height_cm' => ['nullable', 'numeric', 'min:50', 'max:300'],
            'weight_kg' => ['nullable', 'numeric', 'min:20', 'max:500'],
            'blood_type' => ['nullable', 'string', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'allergies' => ['nullable', 'array'],
            'allergies.*.name' => ['required_with:allergies', 'string', 'max:255'],
            'allergies.*.severity' => ['required_with:allergies', 'string', 'in:mild,moderate,severe'],
            'allergies.*.reaction' => ['nullable', 'string', 'max:500'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:50'],
            'emergency_contact_relationship' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'height_cm.min' => 'Height must be at least 50 cm.',
            'height_cm.max' => 'Height must not exceed 300 cm.',
            'weight_kg.min' => 'Weight must be at least 20 kg.',
            'weight_kg.max' => 'Weight must not exceed 500 kg.',
            'blood_type.in' => 'Blood type must be one of: A+, A-, B+, B-, AB+, AB-, O+, O-.',
            'allergies.*.severity.in' => 'Allergy severity must be mild, moderate, or severe.',
        ];
    }
}
