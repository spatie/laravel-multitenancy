<?php

namespace Spatie\Multitenancy\Events;

use Spatie\Multitenancy\Contracts\IsTenant;

class MakingTenantCurrentEvent
{
    public function __construct(
        public IsTenant $tenant
    ) {
    }
}
