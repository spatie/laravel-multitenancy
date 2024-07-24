<?php

namespace Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Context;
use Spatie\Multitenancy\Concerns\UsesMultitenancyConfig;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Valuestore\Valuestore;

class TestJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Dispatchable;
    use UsesMultitenancyConfig;

    public Valuestore $valuestore;

    public function __construct(Valuestore $valuestore)
    {
        $this->valuestore = $valuestore;
    }

    public function handle()
    {
        $this->valuestore->put('tenantIdInContext', Context::get($this->currentTenantContextKey()));
        $this->valuestore->put('tenantId', Tenant::current()?->id);
        $this->valuestore->put('tenantName', Tenant::current()?->name);
    }
}
