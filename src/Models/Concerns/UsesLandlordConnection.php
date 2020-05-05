<?php

namespace Spatie\Multitenancy\Models\Concerns;

trait UsesLandlordConnection
{
    public function getConnectionName()
    {
        return config('multitenancy.landlord_connection_name');
    }
}
