<?php

namespace Spatie\Multitenancy\Tests\Feature\Commands\TestClasses;

use Illuminate\Console\Command;
use Spatie\Multitenancy\Models\Tenant;

class FailForTenantCommand extends Command
{
    protected $signature = 'fail-for-tenant {tenantId?} {--exit-code=1}';

    protected $description = 'Fail for the given tenant, or all tenants when none is given';

    public function handle(): int
    {
        $tenantId = Tenant::current()->id;

        $this->line('Ran for tenant ' . $tenantId);

        return $this->argument('tenantId') === null || $tenantId === (int) $this->argument('tenantId')
            ? (int) $this->option('exit-code')
            : self::SUCCESS;
    }
}
