<?php

namespace Spatie\Multitenancy\Events;

use Spatie\Multitenancy\Contracts\IsTenant;

class MadeTenantCurrentEvent
{
    public function __construct(
        public IsTenant $tenant
    ) {
    }
}
