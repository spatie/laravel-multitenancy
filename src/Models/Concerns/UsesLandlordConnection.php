<?php

namespace Spatie\Multitenancy\Models\Concerns;

trait UsesLandlordConnection
{
    public function getConnectionName()
    {
        return config('multitenancy.landlord_database_connection_name');
    }
}
