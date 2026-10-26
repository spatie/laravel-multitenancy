<?php

namespace Spatie\Multitenancy\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Spatie\Multitenancy\Commands\Concerns\TenantAware;
use Spatie\Multitenancy\Concerns\UsesMultitenancyConfig;
use Spatie\Multitenancy\Contracts\IsTenant;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TenantsArtisanCommand extends Command
{
    use UsesMultitenancyConfig;
    use TenantAware {
        execute as protected executeForTenants;
    }

    protected $signature = 'tenants:artisan {artisanCommand} {--tenant=*} {--continue-on-error}';

    protected $description = 'Run an Artisan command for selected tenants.';

    protected int $artisanCommandExitCode = self::SUCCESS;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->artisanCommandExitCode = self::SUCCESS;

        $exitCode = $this->executeForTenants($input, $output);

        return $exitCode ?: $this->artisanCommandExitCode;
    }

    public function handle(): void
    {
        if (! $artisanCommand = $this->argument('artisanCommand')) {
            $artisanCommand = $this->ask('Which artisan command do you want to run for all tenants?');
        }

        $artisanCommand = addslashes($artisanCommand);

        $tenant = app(IsTenant::class)::current();

        $this->line('');
        $this->info("Running command for tenant `{$tenant->name}` (id: {$tenant->getKey()})...");
        $this->line("---------------------------------------------------------");

        if (Artisan::call($artisanCommand, [], $this->output) !== self::SUCCESS) {
            $this->artisanCommandExitCode = self::FAILURE;
        }
    }
}
