<?php

namespace Spatie\Multitenancy;

use Illuminate\Contracts\Foundation\Application;
use Spatie\Multitenancy\Actions\MakeQueueTenantAwareAction;
use Spatie\Multitenancy\Concerns\UsesMultitenancyConfig;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\Events\TenantNotFoundForRequestEvent;
use Spatie\Multitenancy\Tasks\TasksCollection;
use Spatie\Multitenancy\TenantFinder\TenantFinder;

class Multitenancy
{
    use UsesMultitenancyConfig;

    public function __construct(public Application $app)
    {
    }

    public function boot(): void
    {
        $this
            ->registerBindings()
            ->configureQueue();
    }

    public function start(): void
    {
        $this
            ->registerBindings()
            ->configureRequests();
    }

    public function end(): void
    {
        app(IsTenant::class)::forgetCurrent();
    }

    public function registerBindings(): static
    {
        return $this
            ->registerTenantFinder()
            ->registerTasksCollection();
    }

    protected function determineCurrentTenant(): void
    {
        if (! $this->app['config']->get('multitenancy.tenant_finder')) {
            return;
        }

        /** @var TenantFinder $tenantFinder */
        $tenantFinder = $this->app[TenantFinder::class];

        $tenant = $tenantFinder->findForRequest($this->app['request']);

        if (! $tenant instanceof IsTenant) {
            event(new TenantNotFoundForRequestEvent($this->app['request']));

            return;
        }

        $tenant->makeCurrent();
    }

    protected function registerTasksCollection(): static
    {
        $this->app->singleton(TasksCollection::class, fn () => new TasksCollection(
            $this->app['config']->get('multitenancy.switch_tenant_tasks')
        ));

        return $this;
    }

    protected function registerTenantFinder(): static
    {
        $tenantFinderConfig = $this->app['config']->get('multitenancy.tenant_finder');

        if ($tenantFinderConfig) {
            $this->app->bind(TenantFinder::class, $tenantFinderConfig);
        }

        return $this;
    }

    protected function configureRequests(): static
    {
        if (! $this->app->runningInConsole()) {
            $this->determineCurrentTenant();
        }

        return $this;
    }

    protected function configureQueue(): static
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
