<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\AppointmentReminder;
use App\Notifications\AppointmentReminderNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:send-appointment-reminders')]
#[Description('Queue due appointment reminder emails')]
class SendAppointmentReminders extends Command
{
    public function handle(): int
    {
        $appointments = Appointment::query()
            ->with(['patient', 'service'])
            ->where('status', 'confirmed')
            ->whereBetween('scheduled_for', [now(), now()->addDays(7)])
            ->get();

        foreach ($appointments as $appointment) {
            foreach ([['seven_day', 7], ['two_day', 2], ['one_day', 1]] as [$type, $days]) {
                $scheduledFor = $appointment->scheduled_for->subDays($days);
                if ($scheduledFor->isFuture() || $scheduledFor->lessThan(now()->subMinutes(15))) {
                    continue;
                }

                $reminder = AppointmentReminder::query()->firstOrCreate([
                    'appointment_id' => $appointment->id,
                    'reminder_type' => $type,
                ], [
                    'scheduled_for' => $scheduledFor,
                ]);

                if ($reminder->sent_at === null) {
                    $appointment->patient->notify((new AppointmentReminderNotification($appointment))->afterCommit());
                    $reminder->update(['sent_at' => now()]);
                }
            }
        }

        return self::SUCCESS;
    }
}
