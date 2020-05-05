<?php

namespace Spatie\Multitenancy\Tasks;

use Illuminate\Support\Facades\DB;
use Spatie\Multitenancy\Models\Tenant;

class SwitchTenantDatabase implements MakeTenantCurrentTask
{
    public function makeCurrent(Tenant $tenant): void
    {
        config([
            'database.connections.tenant.database' => $tenant->getDatabaseName(),
        ]);

        DB::purge('tenant');
    }
}
