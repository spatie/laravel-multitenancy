<?php

namespace Spatie\Multitenancy;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\Multitenancy\Actions\MakeQueueTenantAwareAction;
use Spatie\Multitenancy\Commands\TenantsArtisanCommand;
use Spatie\Multitenancy\Concerns\UsesMultitenancyConfig;
use Spatie\Multitenancy\Models\Concerns\UsesTenantModel;
use Spatie\Multitenancy\Tasks\TasksCollection;
use Spatie\Multitenancy\TenantFinder\TenantFinder;

class MultitenancyServiceProvider extends PackageServiceProvider
{
    use UsesTenantModel,
        UsesMultitenancyConfig;

    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-multitenancy')
            ->hasConfigFile()
            ->hasMigration('landlord/create_landlord_tenants_table')
            ->hasCommand(TenantsArtisanCommand::class);
    }

    public function packageBooted()
    {
        $this
            ->registerTenantFinder()
            ->registerTasksCollection()
            ->configureRequests()
            ->configureQueue();
    }

    protected function determineCurrentTenant(): void
    {
        if (! config('multitenancy.tenant_finder')) {
            return;
        }

        /** @var \Spatie\Multitenancy\TenantFinder\TenantFinder $tenantFinder */
        $tenantFinder = app(TenantFinder::class);

        $tenant = $tenantFinder->findForRequest(request());

        $tenant?->makeCurrent();
    }

    protected function registerTasksCollection(): self
    {
        $this->app->singleton(TasksCollection::class, function () {
            $taskClassNames = config('multitenancy.switch_tenant_tasks');

            return new TasksCollection($taskClassNames);
        });

        return $this;
    }

    protected function registerTenantFinder(): self
    {
        if (config('multitenancy.tenant_finder')) {
            $this->app->bind(TenantFinder::class, config('multitenancy.tenant_finder'));
        }

        return $this;
    }

    protected function configureRequests(): self
    {
        if (! $this->app->runningInConsole()) {
            $this->determineCurrentTenant();
        }

        return $this;
    }

    protected function configureQueue(): self
    {
        $this
            ->getMultitenancyActionClass(
                actionName: 'make_queue_tenant_aware_action',
                actionClass: MakeQueueTenantAwareAction::class
            )
            ->execute();

        return $this;
    }
}
