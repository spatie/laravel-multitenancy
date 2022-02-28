<?php

namespace Spatie\Multitenancy\Models\Concerns;

use Spatie\Multitenancy\Concerns\UsesMultitenancyConfig;

trait UsesLandlordConnection
{
    use UsesMultitenancyConfig;

    public function getConnectionName()
    {
        return $this->landlordDatabaseConnectionName();
    }
}
