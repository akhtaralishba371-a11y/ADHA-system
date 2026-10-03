<?php

namespace App\Policies;

use App\Models\Clinician;
use App\Models\User;

class ClinicianPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Clinician $clinician): bool
    {
        return $user->managesClinicians() || $clinician->user_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->managesClinicians();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Clinician $clinician): bool
    {
        if ($user->managesClinicians()) {
            return true;
        }

        return $user->isClinician() && $clinician->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Clinician $clinician): bool
    {
        return $user->managesClinicians();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Clinician $clinician): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Clinician $clinician): bool
    {
        return false;
    }

    public function manageSchedule(User $user, Clinician $clinician): bool
    {
        return $user->managesClinicians() || $clinician->user_id === $user->id;
    }
}
