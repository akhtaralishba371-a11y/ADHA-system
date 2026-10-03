<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;

class PatientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, Patient $patient): bool
    {
        if ($user->managesClinicians()) {
            return true;
        }

        if (! $user->isClinician()) {
            return false;
        }

        return $patient->appointments()
            ->whereHas('clinician', fn ($query) => $query->where('user_id', $user->id))
            ->exists();
    }

    public function create(User $user): bool
    {
        return $user->managesClinicians();
    }

    public function update(User $user, Patient $patient): bool
    {
        return $user->managesClinicians();
    }

    public function delete(User $user, Patient $patient): bool
    {
        return false;
    }
}
