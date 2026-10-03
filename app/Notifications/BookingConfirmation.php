<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingConfirmation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private Appointment $appointment) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your assessment booking request')
            ->greeting('Hello '.$this->appointment->patient->first_name.',')
            ->line('We have received your booking request.')
            ->line('Appointment: '.$this->appointment->service->name)
            ->line('Requested time: '.$this->appointment->scheduled_for->timezone(config('app.timezone'))->format('l, j F Y \a\t H:i'))
            ->line('Reference: '.$this->appointment->booking_reference)
            ->line('Our care team will confirm the appointment shortly.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'appointment_id' => $this->appointment->id,
        ];
    }
}
