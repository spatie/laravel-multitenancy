<?php

namespace Spatie\Multitenancy\Tasks;

use Illuminate\Support\Facades\DB;
use Spatie\Multitenancy\Exceptions\InvalidConfiguration;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\UsesMultitenancyConfig;

class SwitchTenantDatabase implements MakeTenantCurrentTask
{
    use UsesMultitenancyConfig;

    public function makeCurrent(Tenant $tenant): void
    {
        $tenantConnectionName = $this->tenantDatabaseConnectionName();

        if (is_null(config("database.connections.{$tenantConnectionName}"))) {
            throw InvalidConfiguration::tenantConnectionDoesNotExist($tenantConnectionName);
        }

        config([
            "database.connections.{$tenantConnectionName}.database" => $tenant->getDatabaseName(),
        ]);

        DB::purge('tenant');
    }
}
