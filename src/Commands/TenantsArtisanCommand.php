<?php

namespace Spatie\Multitenancy\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Spatie\Multitenancy\Models\Concerns\UsesTenantModel;
use Spatie\Multitenancy\Models\Tenant;

class TenantsArtisanCommand extends Command
{
    use UsesTenantModel;

    protected $signature = 'tenants:artisan {artisanCommand} {--tenant=*}';

    public function handle()
    {
        $tenantQuery = $this->getTenantModel()->newQuery();

        if (! $artisanCommand = $this->argument('artisanCommand')) {
            $artisanCommand = $this->ask('Which artisan command do you want to run for all tenants?');
        }

        if ($tenantIds = $this->option('tenant')) {
            $tenantQuery->whereIn('id', Arr::wrap($tenantIds));
        }

        $tenantQuery
            ->cursor()
            ->each(
                fn (Tenant $tenant) => $this->runArtisanCommandForTenant($tenant, $artisanCommand)
            );

        $this->info('All done!');
    }

    public function runArtisanCommandForTenant(Tenant $tenant, string $artisanCommand): void
    {
        $this->line('');
        $this->info("Running command for tenant `{$tenant->name}` (id: {$tenant->getKey()})...");
        $this->line("---------------------------------------------------------");

        $tenant->makeCurrent();

        Artisan::call($artisanCommand, [], $this->output);
    }
}
