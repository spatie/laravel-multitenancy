<?php

namespace Spatie\Multitenancy\Events;

use Spatie\Multitenancy\Contracts\IsTenant;

class ForgotCurrentTenantEvent
{
    public function __construct(
        public IsTenant $tenant
    ) {
    }
}
