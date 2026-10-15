<?php

namespace Spatie\Multitenancy\Tests\Feature\Tasks\TestClasses;

use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\Tasks\SwitchTenantTask;

class RegisterTenantRoutesTask implements SwitchTenantTask
{
    public function makeCurrent(IsTenant $tenant): void
    {
        app('router')->get('tenant-only', fn () => 'tenant');
    }

    public function forgetCurrent(): void
    {
    }
}
