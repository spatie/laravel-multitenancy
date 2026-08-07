<?php

namespace Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Spatie\Multitenancy\Jobs\TenantAware;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Valuestore\Valuestore;

class DispatchesNestedJobTestJob implements ShouldQueue, TenantAware
{
    use Dispatchable;
    use InteractsWithQueue;

    public function __construct(
        public Valuestore $valuestore,
    ) {
    }

    public function handle(): void
    {
        dispatch(new NotTenantAwareTestJob($this->valuestore));

        $this->valuestore->put('tenantIdAfterNestedJob', Tenant::current()?->id);
    }
}
