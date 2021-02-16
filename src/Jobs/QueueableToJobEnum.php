<?php

namespace Spatie\Multitenancy\Jobs;

use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Mail\SendQueuedMailable;
use Illuminate\Notifications\SendQueuedNotifications;
use Spatie\Enum\Enum;

class QueueableToJobEnum extends Enum
{
    protected static function values()
    {
        return [
            SendQueuedMailable::class => 'mailable',
            SendQueuedNotifications::class => 'notification',
            CallQueuedListener::class => 'class',
            BroadcastEvent::class => 'event',
        ];
    }
}
