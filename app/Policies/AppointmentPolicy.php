<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, Appointment $appointment): bool
    {
        if ($user->managesClinicians()) {
            return true;
        }

        return $user->isClinician() && $appointment->clinician?->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->managesClinicians() || $user->isClinician();
    }

    public function updateStatus(User $user, Appointment $appointment): bool
    {
        return $this->view($user, $appointment);
    }

    public function reschedule(User $user, Appointment $appointment): bool
    {
        if (in_array($appointment->status, ['cancelled', 'completed'], true)) {
            return false;
        }

        return $this->view($user, $appointment);
    }

    public function update(User $user, Appointment $appointment): bool
    {
        return $this->reschedule($user, $appointment);
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        return false;
    }

    public function restore(User $user, Appointment $appointment): bool
    {
        return false;
    }

    public function forceDelete(User $user, Appointment $appointment): bool
    {
        return false;
    }
}
