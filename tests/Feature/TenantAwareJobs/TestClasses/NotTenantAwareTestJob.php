<?php

namespace Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses;

use Spatie\Multitenancy\Jobs\NotTenantAware;

class NotTenantAwareTestJob extends TestJob implements NotTenantAware
{
}
