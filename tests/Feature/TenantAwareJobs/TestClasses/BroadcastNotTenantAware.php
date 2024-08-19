<?php

namespace Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Spatie\Multitenancy\Jobs\NotTenantAware;

class BroadcastNotTenantAware implements ShouldBroadcast, NotTenantAware
{
    public function __construct(
        public string $message,
    ) {
    }

    public function broadcastOn()
    {
        return [
            new Channel('test-channel'),
        ];
    }
}
