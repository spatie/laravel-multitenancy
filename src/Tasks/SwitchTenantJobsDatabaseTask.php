<?php

namespace Spatie\Multitenancy\Tasks;

use Spatie\Multitenancy\Concerns\UsesMultitenancyConfig;
use Spatie\Multitenancy\Models\Tenant;

class SwitchTenantJobsDatabaseTask implements SwitchTenantTask
{
    use UsesMultitenancyConfig;

    protected $defaultJobDatabaseConnection;

    public function __construct()
    {
        $this->defaultJobDatabaseConnection = config('queue.failed.database');
    }

    public function makeCurrent(Tenant $tenant): void
    {
        config([
            'queue.failed.database' => $this->tenantDatabaseConnectionName(),
            'queue.connections.database.connection' => $this->tenantDatabaseConnectionName(),
        ]);
    }

    public function forgetCurrent(): void
    {
        config([
            'queue.failed.database' => $this->defaultJobDatabaseConnection,
            'queue.connections.database.connection' => null,
        ]);
    }
}
