<?php

namespace Spatie\Multitenancy\Events;

use Spatie\Multitenancy\Models\Tenant;

class MadeTenantCurrentEvent
{
    public Tenant $tenant;

    public function __construct(Tenant $tenant)
    {
        $this->tenant = $tenant;
    }
}
