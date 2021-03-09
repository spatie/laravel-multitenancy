<?php

namespace Spatie\Multitenancy\Events;

use Spatie\Multitenancy\Models\Tenant;

class MadeTenantCurrentEvent
{
    public function __construct(
        public Tenant $tenant
    ) {
    }
}
