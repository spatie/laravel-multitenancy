<?php

namespace Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses;

use Spatie\Multitenancy\Jobs\TenantAware;
use Spatie\Multitenancy\Models\Tenant;

class DispatchesNestedJobTestJob extends TestJob implements TenantAware
{
    public function handle()
    {
        dispatch(new NotTenantAwareTestJob($this->valuestore));

        $this->valuestore->put('tenantIdAfterNestedJob', Tenant::current()?->id);
    }
}
