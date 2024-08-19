<?php

namespace Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses;

use Illuminate\Contracts\Queue\ShouldQueue;
use Spatie\Multitenancy\Jobs\TenantAware;

class ListenerTenantAware implements ShouldQueue, TenantAware
{
    public function handle(TestEvent $event): void
    {

    }
}
