<?php

namespace Spatie\Multitenancy\Commands;

use Illuminate\Console\Command;
use Spatie\Multitenancy\Models\Tenant;

class MigrateTenantsCommand extends Command
{
    protected $signature = 'tenants:migrate {tenantId?} {--fresh} {--seed}';

    public function handle()
    {
        $tenantQuery = Tenant::query();

        if ($this->argument('tenant')) {
            $tenantQuery = $tenantQuery->where('id', $this->argument('tenantId'));
        }

        $tenantQuery::cursor()->each(fn (Tenant $tenant) => $this->migrateTenant($tenant));
    }

    public function migrateTenant(Tenant $tenant): void
    {
        $tenant->configure()->makeCurrent();

        $this->line('');
        $this->info("Migrating tenant `{$tenant->name}` (id: {$tenant->Id})...");
        $this->line("-----------------------------------------");

        $options = ['--force' => true];

        if ($this->option('seed')) {
            $options['--seed'] = true;
        }

        $migrationCommand = $this->option('fresh') ? 'migrate:fresh' : 'migrate';

        $this->call($migrationCommand, $options);
    }
}
