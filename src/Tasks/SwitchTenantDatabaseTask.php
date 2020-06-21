<?php

namespace Spatie\Multitenancy\Tasks;

use Illuminate\Support\Facades\DB;
use Spatie\Multitenancy\Concerns\UsesMultitenancyConfig;
use Spatie\Multitenancy\Exceptions\InvalidConfiguration;
use Spatie\Multitenancy\Models\Tenant;

class SwitchTenantDatabaseTask implements SwitchTenantTask
{
    use UsesMultitenancyConfig;

    private string $originalDefaultConnection;

    public function makeCurrent(Tenant $tenant): void
    {
        $this->originalDefaultConnection = config('database.default');

        $this->setTenantConnectionDatabaseName($tenant->getDatabaseName());
    }

    public function forgetCurrent(): void
    {
        $this->setTenantConnectionDatabaseName(null);

        config([
            "database.default" => $this->originalDefaultConnection,
        ]);
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

        if ($this->tenantDatabaseConnectionAsDefault()) {
            config([
                "database.default" => $this->tenantDatabaseConnectionName(),
            ]);
        }

        DB::purge($tenantConnectionName);
    }
}
