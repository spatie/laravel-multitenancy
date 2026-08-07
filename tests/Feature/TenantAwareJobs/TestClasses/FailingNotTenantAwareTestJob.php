<?php

namespace Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses;

use Exception;
use Spatie\Multitenancy\Jobs\NotTenantAware;

class FailingNotTenantAwareTestJob extends TestJob implements NotTenantAware
{
    public int $tries = 1;

    public function handle()
    {
        throw new Exception('Intentional failure so the job does not finish successfully.');
    }
}
