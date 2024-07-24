<?php

namespace Spatie\Multitenancy\Tasks;

use Spatie\Multitenancy\Contracts\IsTenant;

interface SwitchTenantTask
{
    public function makeCurrent(IsTenant $tenant): void;

    public function forgetCurrent(): void;
}
