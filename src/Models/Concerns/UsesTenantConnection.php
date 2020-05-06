<?php

namespace Spatie\Multitenancy\Models\Concerns;

use Spatie\Multitenancy\UsesMultitenancyConfig;

trait UsesTenantConnection
{
    use UsesMultitenancyConfig;

    public function getConnectionName()
    {
        return $this->tenantDatabaseConnectionName();
    }
}
