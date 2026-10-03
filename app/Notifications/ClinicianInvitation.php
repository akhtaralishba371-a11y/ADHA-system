<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClinicianInvitation extends Notification
{
    public function __construct(public string $activationUrl, public int $expiresInMinutes) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Activate your Jelly clinician account')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('You have been invited to join the Jelly care workspace as a clinician.')
            ->line('Use the secure link below to choose your own password and activate your account.')
            ->action('Activate account', $this->activationUrl)
            ->line('This invitation expires in '.$this->expiresInMinutes.' minutes and can only be used once.')
            ->line('If you did not expect this invitation, you can ignore this email.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'expires_in_minutes' => $this->expiresInMinutes,
        ];
    }
}
