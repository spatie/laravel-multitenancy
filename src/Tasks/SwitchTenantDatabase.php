<?php

namespace Spatie\Multitenancy\Tasks;

use Illuminate\Support\Facades\DB;
use Spatie\Multitenancy\Exceptions\InvalidConfiguration;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Concerns\UsesMultitenancyConfig;

class SwitchTenantDatabase implements SwitchTenantTask
{
    use UsesMultitenancyConfig;

    public function makeCurrent(Tenant $tenant): void
    {
        $this->setTenantConnectionDatabaseName($tenant->getDatabaseName());
    }

    public function forgetCurrent(): void
    {
        $this->setTenantConnectionDatabaseName(null);
    }

    protected function setTenantConnectionDatabaseName(?string $databaseName)
    {
        $tenantConnectionName = $this->tenantDatabaseConnectionName();

        if (is_null(config("database.connections.{$tenantConnectionName}"))) {
            throw InvalidConfiguration::tenantConnectionDoesNotExist($tenantConnectionName);
        }

        config([
            "database.connections.{$tenantConnectionName}.database" => $databaseName,
        ]);

        DB::purge($tenantConnectionName);
    }
}
