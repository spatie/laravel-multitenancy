<?php

namespace Spatie\Multitenancy\Events;

use Spatie\Multitenancy\Models\Tenant;

class ForgotCurrentTenantEvent
{
    public function __construct(
        public Tenant $tenant
    ) {
    }
}
