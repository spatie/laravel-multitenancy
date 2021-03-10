<?php

namespace Spatie\Multitenancy;

use Illuminate\Support\ServiceProvider;
use Spatie\Multitenancy\Actions\MakeQueueTenantAwareAction;
use Spatie\Multitenancy\Commands\TenantsArtisanCommand;
use Spatie\Multitenancy\Concerns\UsesMultitenancyConfig;
use Spatie\Multitenancy\Models\Concerns\UsesTenantModel;
use Spatie\Multitenancy\Tasks\TasksCollection;
use Spatie\Multitenancy\TenantFinder\TenantFinder;

class MultitenancyServiceProvider extends ServiceProvider
{
    use UsesTenantModel,
        UsesMultitenancyConfig;

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this
                ->registerPublishables();
        }

        $this
            ->bootCommands()
            ->registerTenantFinder()
            ->registerTasksCollection()
            ->configureRequests()
            ->configureQueue();
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/multitenancy.php', 'multitenancy');
    }

    protected function registerPublishables(): self
    {
        $this->publishes(paths: [
            __DIR__ . '/../config/multitenancy.php' => config_path('multitenancy.php'),
        ], groups: 'config');

        if (! class_exists('CreateLandlordTenantsTable')) {
            $this->publishes(paths: [
                __DIR__ . '/../database/migrations/landlord/create_landlord_tenants_table.php.stub' => database_path('migrations/landlord/' . date('Y_m_d_His', time()) . '_create_landlord_tenants_table.php'),
            ], groups: 'migrations');
        }

        return $this;
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

    protected function bootCommands(): self
    {
        $this->commands([
            TenantsArtisanCommand::class,
        ]);

        return $this;
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
