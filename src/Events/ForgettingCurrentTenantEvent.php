<?php

namespace Spatie\Multitenancy\Events;

use Spatie\Multitenancy\Contracts\IsTenant;

class ForgettingCurrentTenantEvent
{
    public function __construct(
        public IsTenant $tenant
    ) {
    }
}
