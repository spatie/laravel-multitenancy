<?php

namespace Spatie\Multitenancy\Tests\Feature\Commands\TestClasses;

use Illuminate\Console\Command;
use RuntimeException;
use Spatie\Multitenancy\Models\Tenant;

class ThrowForTenantCommand extends Command
{
    protected $signature = 'throw-for-tenant {tenantId?} {--exit-code=0}';

    protected $description = 'Throw when running for the given tenant';

    public static array $ranForTenantIds = [];

    public function handle(): int
    {
        $tenantId = Tenant::current()->id;

        static::$ranForTenantIds[] = $tenantId;

        if ($this->argument('tenantId') === null || $tenantId === (int) $this->argument('tenantId')) {
            throw new RuntimeException("Failed for tenant {$tenantId}");
        }

        return (int) $this->option('exit-code');
    }
}
