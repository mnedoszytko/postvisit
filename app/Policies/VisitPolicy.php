<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Visit;

class VisitPolicy
{
    /**
     * Patient can view their own visits; doctor can view visits they conducted; admin sees all.
     */
    public function view(User $user, Visit $visit): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isPatient() && $user->patient_id === $visit->patient_id) {
            return true;
        }

        if ($user->isDoctor() && $user->practitioner_id === $visit->practitioner_id) {
            return true;
        }

        return false;
    }

    /**
     * Only the conducting doctor or admin can update a visit.
     */
    public function update(User $user, Visit $visit): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isDoctor() && $user->practitioner_id === $visit->practitioner_id;
    }

    /**
     * Patients can create visits for themselves; doctors and admins can create for anyone.
     */
    public function create(User $user): bool
    {
        return true;
    }
}
