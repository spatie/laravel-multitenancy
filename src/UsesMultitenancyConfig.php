<?php

namespace Spatie\Multitenancy;

trait UsesMultitenancyConfig
{
    public function tenantDatabaseConnectionName(): ?string
    {
        return config('multitenancy.tenant_database_connection_name') ?? config('database.default');
    }

    public function landlordDatabaseConnectionName(): ?string
    {
        return config('multitenancy.landlord_database_connection_name') ?? config('database.default');
    }

    public function currentTenantContainerKey(): string
    {
        return config('multitenancy.current_tenant_container_key');
    }
}
