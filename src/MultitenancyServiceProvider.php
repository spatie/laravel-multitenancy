<?php declare(strict_types=1);

namespace Spatie\Multitenancy;

use Illuminate\Support\ServiceProvider;
use Spatie\Multitenancy\Tasks\TasksCollection;
use Spatie\Multitenancy\TenantFinder\TenantFinder;
use Spatie\Multitenancy\Commands\TenantsArtisanCommand;
use Spatie\Multitenancy\Concerns\UsesMultitenancyConfig;
use Spatie\Multitenancy\Models\Concerns\UsesTenantModel;
use Spatie\Multitenancy\Actions\MakeQueueTenantAwareAction;

class MultitenancyServiceProvider extends ServiceProvider
{
    use UsesTenantModel,
        UsesMultitenancyConfig;

    public function boot(): void
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

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/multitenancy.php', 'multitenancy');
    }

    protected function registerPublishables(): self
    {
        $this->publishes([
            __DIR__ . '/../config/multitenancy.php' => config_path('multitenancy.php'),
        ], 'config');

        if (! class_exists('CreateLandlordTenantsTable')) {
            $this->publishes([
                __DIR__ . '/../database/migrations/landlord/create_landlord_tenants_table.php.stub' => database_path('migrations/landlord/' . date('Y_m_d_His', time()) . '_create_landlord_tenants_table.php'),
            ], 'migrations');
        }

        return $this;
    }

    protected function determineCurrentTenant(): void
    {
        if (! config('multitenancy.tenant_finder')) {
            return;
        }

        /** @var TenantFinder $tenantFinder */
        $tenantFinder = app(TenantFinder::class);

        $tenant = $tenantFinder->findForRequest(request());

        optional($tenant)->makeCurrent();
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
                'make_queue_tenant_aware_action',
                MakeQueueTenantAwareAction::class
            )
            ->execute();

        return $this;
    }
}
