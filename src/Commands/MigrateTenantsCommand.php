<?php

namespace Spatie\Multitenancy\Commands;

use Illuminate\Console\Command;
use Spatie\Multitenancy\Actions\MigrateTenantAction;
use Spatie\Multitenancy\Concerns\UsesMultitenancyConfig;
use Spatie\Multitenancy\Models\Tenant;

class MigrateTenantsCommand extends Command
{
    use UsesMultitenancyConfig;

    protected $signature = 'tenants:migrate {tenantId?} {--fresh} {--seed}';

    public function handle()
    {
        $tenantQuery = Tenant::query();

        if ($this->argument('tenantId')) {
            $tenantQuery = $tenantQuery->where('id', $this->argument('tenantId'));
        }

        $tenantQuery->cursor()->each(fn (Tenant $tenant) => $this->migrateTenant($tenant));
    }

    public function migrateTenant(Tenant $tenant): void
    {
        $this->line('');
        $this->info("Migrating tenant `{$tenant->name}` (id: {$tenant->Id})...");
        $this->line("---------------------------------------------------------");

        /** @var \Spatie\Multitenancy\Actions\MigrateTenantAction $migrateTenantAction */
        $migrateTenantAction = $this->getMultitenancyActionClass('migrate_tenant', MigrateTenantAction::class);

        $migrateTenantAction
            ->output($this->output)
            ->fresh($this->option('fresh'))
            ->seed($this->option('seed'))
            ->execute($tenant);
    }
}
