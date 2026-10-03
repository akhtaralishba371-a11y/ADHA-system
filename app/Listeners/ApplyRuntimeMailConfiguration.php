<?php

namespace App\Listeners;

use App\Services\RuntimeMailConfigurator;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Notifications\Events\NotificationSending;

class ApplyRuntimeMailConfiguration
{
    public function __construct(private RuntimeMailConfigurator $mailConfigurator) {}

    public function handleNotificationSending(NotificationSending $event): void
    {
        $this->mailConfigurator->apply();
    }

    public function handleMessageSending(MessageSending $event): void
    {
        $this->mailConfigurator->apply();
    }
}
