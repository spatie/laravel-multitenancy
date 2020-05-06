<?php

namespace Spatie\Multitenancy\Tasks;

use Illuminate\Support\Facades\DB;
use Spatie\Multitenancy\Exceptions\InvalidConfiguration;
use Spatie\Multitenancy\Models\Tenant;

class SwitchTenantDatabase implements MakeTenantCurrentTask
{
    public function makeCurrent(Tenant $tenant): void
    {
        $tenantConnectionName = config('multitenancy.tenant_database_connection_name');

        if (is_null(config("database.connections.{$tenantConnectionName}"))) {
            throw InvalidConfiguration::tenantConnectionDoesNotExist();
        }

        config([
            "database.connections.{$tenantConnectionName}.database" => $tenant->getDatabaseName(),
        ]);

        DB::purge('tenant');
    }
}
