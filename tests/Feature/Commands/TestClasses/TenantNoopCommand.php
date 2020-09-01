<?php

namespace Spatie\Multitenancy\Tests\Feature\Commands\TestClasses;

use Illuminate\Console\Command;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Commands\Concerns\TenantAware;

class TenantNoopCommand extends Command
{
    use TenantAware;

    protected $signature = 'tenant:noop {--tenant=*}';

    protected $description = 'Execute noop for tenant(s)';

    public function handle()
    {
        $this->line('Tenant ID is ' . Tenant::current()->id);
    }
}
