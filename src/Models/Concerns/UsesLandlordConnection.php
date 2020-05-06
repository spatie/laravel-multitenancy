<?php

namespace Spatie\Multitenancy\Models\Concerns;

use Spatie\Multitenancy\UsesMultitenancyConfig;

trait UsesLandlordConnection
{
    use UsesMultitenancyConfig;

    public function getConnectionName()
    {
        return $this->landlordDatabaseConnectionName();
    }
}
