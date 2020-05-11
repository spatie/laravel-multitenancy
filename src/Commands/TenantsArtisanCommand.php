<?php

namespace Spatie\Multitenancy\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Spatie\Multitenancy\Models\Tenant;

class TenantsArtisanCommand extends Command
{
    protected $signature = 'tenants:artisan {artisanCommand} {--tenant=*}';

    public function handle()
    {
        $tenantQuery = Tenant::query();

        if ($tenantIds = $this->option('tenant')) {
            $tenantQuery = $tenantQuery->whereIn('id', $tenantIds);
        }

        $artisanCommand = $this->argument('artisanCommand');

        $tenantQuery->cursor()
            ->each(
                fn (Tenant $tenant) => $this->runArtisanCommandForTenant($tenant, $artisanCommand)
            );

        $this->info('All done!');
    }

    public function runArtisanCommandForTenant(Tenant $tenant, string $artisanCommand): void
    {
        $this->line('');
        $this->info("Running command for tenant `{$tenant->name}` (id: {$tenant->Id})...");
        $this->line("---------------------------------------------------------");

        $tenant->makeCurrent();

        Artisan::call($artisanCommand, [], $this->output);
    }
}
