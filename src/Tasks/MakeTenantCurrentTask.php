<?php

namespace Spatie\Multitenancy\Tasks;

use Spatie\Multitenancy\Models\Tenant;

interface MakeTenantCurrentTask
{
    public function makeCurrent(Tenant $tenant): void;

}
