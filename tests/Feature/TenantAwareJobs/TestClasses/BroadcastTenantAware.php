<?php

namespace Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Spatie\Multitenancy\Jobs\TenantAware;

class BroadcastTenantAware implements ShouldBroadcast, TenantAware
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
