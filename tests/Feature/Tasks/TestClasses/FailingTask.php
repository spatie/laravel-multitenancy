<?php

namespace Spatie\Multitenancy\Tests\Feature\Tasks\TestClasses;

use RuntimeException;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\Tasks\SwitchTenantTask;

class FailingTask implements SwitchTenantTask
{
    public bool $forgotCurrentCalled = false;

    public function makeCurrent(IsTenant $tenant): void
    {
        throw new RuntimeException('This task cannot make a tenant current');
    }

    public function forgetCurrent(): void
    {
        $this->forgotCurrentCalled = true;
    }
}
