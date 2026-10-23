<?php

namespace Spatie\Multitenancy\Tests\Feature\Commands\TestClasses;

use Illuminate\Console\Command;
use Spatie\Multitenancy\Models\Tenant;

class FailForTenantCommand extends Command
{
    protected $signature = 'fail-for-tenant {tenantId}';

    protected $description = 'Fail when running for the given tenant';

    public function handle(): int
    {
        $tenantId = Tenant::current()->id;

        $this->line('Ran for tenant ' . $tenantId);

        return $tenantId === (int) $this->argument('tenantId')
            ? self::FAILURE
            : self::SUCCESS;
    }
}
