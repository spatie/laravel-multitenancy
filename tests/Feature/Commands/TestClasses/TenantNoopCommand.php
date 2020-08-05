<?php

namespace Spatie\Multitenancy\Tests\Feature\Commands\TestClasses;

use Illuminate\Console\Command;
use Spatie\Multitenancy\Commands\Concerns\IsTenantAware;
use Spatie\Multitenancy\Models\Tenant;

class TenantNoopCommand extends Command
{
    use IsTenantAware;

    protected $signature = 'tenant:noop {--tenant=*}';

    protected $description = 'Execute noop for tenant(s)';

    public function handle()
    {
        $this->line('Tenant ID is '. Tenant::current()->id);
    }
}
