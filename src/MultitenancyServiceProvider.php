<?php

namespace Spatie\Multitenancy;

use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\ServiceProvider;
use Spatie\Multitenancy\Commands\MigrateTenantsCommand;
use Spatie\Multitenancy\Exceptions\InvalidConfiguration;
use Spatie\Multitenancy\Models\Concerns\UsesTenantModel;
use Spatie\Multitenancy\TenantFinder\TenantFinder;

class MultitenancyServiceProvider extends ServiceProvider
{
    use UsesTenantModel;

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this
                ->registerPublishables()
                ->bootCommands();
        }

        if (config('multitenancy.tenant_finder')) {
            $this->app->bind(TenantFinder::class, config('multitenancy.tenant_finder'));
        }

        $this
            ->validateConfiguration()
            ->configureRequests()
            ->configureQueue();
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/multitenancy.php', 'multitenancy');
    }

    protected function registerPublishables(): self
    {
        $this->publishes([
            __DIR__ . '/../config/multitenancy.php' => config_path('multitenancy.php'),
        ], 'config');

        if (! class_exists('CreateLandlordTenantsTable')) {
            $this->publishes([
                __DIR__ . '/../database/migrations/landlord/create_landlord_tenants_table.php.stub' => database_path('migrations/landlord' . date('Y_m_d_His', time()) . '_create_tenants_table.php'),
            ], 'migrations');
        }

        return $this;
    }

    protected function validateConfiguration(): self
    {
        $tenantConnectionName = config('multitenancy.tenant_database_connection_name');

        if (is_null(config("database.connections.{$tenantConnectionName}"))) {
            throw InvalidConfiguration::tenantConnectionDoesNotExist();
        }

        $landlordConnectionName = config('multitenancy.landlord_database_connection_name');

        if (is_null(config("database.connections.{$landlordConnectionName}"))) {
            throw InvalidConfiguration::tenantConnectionDoesNotExist();
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
        if (! config('multitenancy.tenant_aware_queue')) {
            return $this;
        }

        $containerKey = config('multitenancy.current_tenant_container_key');

        $this->app['queue']->createPayloadUsing(function () use ($containerKey) {
            return $this->app[$containerKey]
                ? ['tenant_id' => $this->app['currentTenant']->id]
                : [];
        });

        $this->app['events']->listen(JobProcessing::class, function ($event) {
            if (isset($event->job->payload()['tenant_id'])) {
                /** @var \Spatie\Multitenancy\Models\Tenant $tenant */
                $tenant = $this->getTenantModel()::find($event->job->payload()['tenant_id']);

                $tenant->makeCurrent();
            }
        });

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

        optional($tenant)->makeCurrent();
    }

    protected function bootCommands(): self
    {
        $this->commands([
            MigrateTenantsCommand::class,
        ]);

        return $this;
    }
}
