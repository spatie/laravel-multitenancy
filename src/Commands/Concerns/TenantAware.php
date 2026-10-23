<?php

namespace Spatie\Multitenancy\Commands\Concerns;

use Illuminate\Support\Arr;
use Spatie\Multitenancy\Concerns\UsesMultitenancyConfig;
use Spatie\Multitenancy\Contracts\IsTenant;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

trait TenantAware
{
    use UsesMultitenancyConfig;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $tenants = Arr::wrap($this->option('tenant'));

        $tenantQuery = app(IsTenant::class)::query()
            ->when(! blank($tenants), function ($query) use ($tenants) {
                collect($this->getTenantArtisanSearchFields())
                    ->each(fn ($field) => $query->orWhereIn($field, $tenants));
            });

        if ($tenantQuery->count() === 0) {
            if (blank($tenants)) {
                $this->warn('No tenants found, skipping.');

                return self::SUCCESS;
            }

            $this->error('No tenant(s) found.');

            return self::FAILURE;
        }

        $continueOnError = $this->hasOption('continue-on-error') && $this->option('continue-on-error');

        return $tenantQuery
            ->cursor()
            ->map(function (IsTenant $tenant) use ($continueOnError) {
                try {
                    return $tenant->execute(fn () => (int) $this->laravel->call([$this, 'handle']));
                } catch (Throwable $exception) {
                    if (! $continueOnError) {
                        throw $exception;
                    }

                    report($exception);

                    $this->error("Tenant (id: {$tenant->getKey()}) failed: {$exception->getMessage()}");

                    return self::FAILURE;
                }
            })
            ->sum();
    }
}
