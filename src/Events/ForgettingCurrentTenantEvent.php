<?php

namespace Spatie\Multitenancy\Events;

use Spatie\Multitenancy\Models\Tenant;

class ForgettingCurrentTenantEvent
{
    public function __construct(
        public Tenant $tenant
    ) { }
}
