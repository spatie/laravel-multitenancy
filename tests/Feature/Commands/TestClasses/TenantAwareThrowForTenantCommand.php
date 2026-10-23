<?php

namespace Spatie\Multitenancy\Tests\Feature\Commands\TestClasses;

use Spatie\Multitenancy\Commands\Concerns\TenantAware;

class TenantAwareThrowForTenantCommand extends ThrowForTenantCommand
{
    use TenantAware;

    protected $signature = 'tenant:throw-for-tenant {tenantId} {--tenant=*} {--continue-on-error}';
}
