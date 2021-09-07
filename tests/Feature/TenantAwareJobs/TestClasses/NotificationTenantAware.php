<?php

namespace Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Spatie\Multitenancy\Jobs\TenantAware;

class NotificationTenantAware extends Notification implements ShouldQueue, TenantAware
{
    use Queueable;

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage())
            ->subject('Message')
            ->greeting('Hello!')
            ->line('Say goodbye!');
    }

    public function toArray($notifiable)
    {
        return [ ];
    }
}
