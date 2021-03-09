<?php

namespace Spatie\Multitenancy\Events;

use Spatie\Multitenancy\Models\Tenant;

class MakingTenantCurrentEvent
{
    public function __construct(
        public Tenant $tenant
    ) { }
}
