<?php

namespace Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses;

use Exception;
use Spatie\Multitenancy\Jobs\TenantAware;

class FailingTenantAwareTestJob extends TestJob implements TenantAware
{
    public int $tries = 1;

    public function handle()
    {
        if ($this->valuestore->get('shouldFail', true)) {
            throw new Exception('Intentional failure so the job lands in failed_jobs.');
        }

        parent::handle();
    }
}
