<?php


namespace Spatie\Multitenancy\Models\Concerns;

trait UsesTenantConnection
{
    public function getConnectionName()
    {
        return config('multitenancy.tenant_connection_name');
    }
}
