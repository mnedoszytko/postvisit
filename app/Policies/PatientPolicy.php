<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;

class PatientPolicy
{
    /**
     * Patient can view their own record; doctors can view patients they have visits with; admin sees all.
     */
    public function view(User $user, Patient $patient): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isPatient() && $user->patient_id === $patient->id) {
            return true;
        }

        if ($user->isDoctor()) {
            return $patient->visits()
                ->where('practitioner_id', $user->practitioner_id)
                ->exists();
        }

        return false;
    }

    /**
     * Only the patient themselves or an admin can update patient data.
     */
    public function update(User $user, Patient $patient): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isPatient() && $user->patient_id === $patient->id;
    }
}
