<?php

namespace Spatie\Multitenancy\Commands\Concerns;

use Illuminate\Support\Arr;
use Spatie\Multitenancy\Concerns\UsesMultitenancyConfig;
use Spatie\Multitenancy\Models\Tenant;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

trait IsTenantAware
{
    use UsesMultitenancyConfig;

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $tenants = Arr::wrap($this->option('tenant'));

        $tenantQuery = Tenant::query()
            ->when(! blank($tenants), function ($query) use ($tenants) {
                collect($this->getTenantArtisanSearchFields())
                    ->each(fn ($field) => $query->orWhereIn($field, Arr::wrap($tenants)));
            });

        if ($tenantQuery->count() === 0) {
            $this->error('No tenant(s) found.');

            return -1;
        }

        return $tenantQuery
            ->cursor()
            ->map(fn ($tenant) => $tenant->execute(fn () => (int) $this->laravel->call([$this, 'handle'])))
            ->sum();
    }
}
