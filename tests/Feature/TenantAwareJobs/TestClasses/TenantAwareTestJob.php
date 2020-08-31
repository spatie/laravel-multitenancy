<?php declare(strict_types=1);

namespace Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses;

use Spatie\Multitenancy\Jobs\TenantAware;

class TenantAwareTestJob extends TestJob implements TenantAware
{
}
