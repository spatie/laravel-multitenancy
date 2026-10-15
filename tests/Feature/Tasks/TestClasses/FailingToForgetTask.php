<?php

namespace Spatie\Multitenancy\Tests\Feature\Tasks\TestClasses;

use RuntimeException;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\Tasks\SwitchTenantTask;

class FailingToForgetTask implements SwitchTenantTask
{
    public function makeCurrent(IsTenant $tenant): void
    {
    }

    public function forgetCurrent(): void
    {
        throw new RuntimeException('This task cannot forget the current tenant');
    }
}
