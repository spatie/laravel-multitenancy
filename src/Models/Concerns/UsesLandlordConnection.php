<?php

namespace Spatie\Multitenancy\Models\Concerns;

trait UsesLandlordConnection
{
    public function getConnectionName()
    {
        return 'landlord';
    }
}
