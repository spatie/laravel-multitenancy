<?php

namespace Spatie\Multitenancy\Models\Concerns;

use Spatie\Multitenancy\Concerns\UsesMultitenancyConfig;

trait UsesTenantConnection
{
    use UsesMultitenancyConfig;

    public function getConnectionName(): string
    {
        return $this->tenantDatabaseConnectionName();
    }
}
