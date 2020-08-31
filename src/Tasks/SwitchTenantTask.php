<?php declare(strict_types=1);

namespace Spatie\Multitenancy\Tasks;

use Spatie\Multitenancy\Models\Tenant;

interface SwitchTenantTask
{
    public function makeCurrent(Tenant $tenant): void;

    public function forgetCurrent(): void;
}
