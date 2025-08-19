<?php

namespace Spatie\Multitenancy\Tests\Feature\Commands\TestClasses;

use Illuminate\Console\Command;
use Spatie\Multitenancy\Commands\Concerns\TenantAware;
use Spatie\Multitenancy\Models\Tenant;

class TenantBeforeCommand extends Command
{
    use TenantAware;

    protected $signature = 'tenant:before {--tenant=*}';

    protected $description = 'Execute before for tenant(s)';

    protected int $counter = 0;

    public function handle()
    {
        $this->counter++;

        $this->line('Tenant count is: '. $this->counter);
    }

    public function before(): void
    {
        $this->counter = 0;
    }
}
