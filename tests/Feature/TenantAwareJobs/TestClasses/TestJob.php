<?php

namespace Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses;

use Spatie\Valuestore\Valuestore;
use Spatie\Multitenancy\Models\Tenant;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class TestJob implements ShouldQueue
{
    use InteractsWithQueue;

    public Valuestore $valuestore;

    public function __construct(Valuestore $valuestore)
    {
        $this->valuestore = $valuestore;
    }

    public function handle()
    {
        $this->valuestore->put('tenantIdInPayload', $this->job->payload()['tenantId'] ?? null);
        $this->valuestore->put('tenantId', optional(Tenant::current())->id);
    }
}
