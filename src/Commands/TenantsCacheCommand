<?php

namespace Spatie\Multitenancy\Commands;

use Illuminate\Console\Command;
use Spatie\Multitenancy\Concerns\UsesMultitenancyCaching;

class TenantsCacheCommand extends Command
{
    use UsesMultitenancyCaching;

    protected $signature = 'tenants:cache';

    protected $description = 'Cache tenant models';

    public function handle()
    {
        $this->info('Caching tenants...');

        $this->createTenantCache();
    }
}
