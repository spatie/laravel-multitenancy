<?php

namespace Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses;

use Spatie\Multitenancy\Jobs\TenantAware;

class TenantAwareTestJob extends TestJob implements TenantAware
{
}
