<?php

namespace Spatie\Multitenancy\Actions;

use Spatie\Multitenancy\Events\ForgettingCurrentTenantEvent;
use Spatie\Multitenancy\Events\ForgotCurrentTenantEvent;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tasks\SwitchTenantTask;
use Spatie\Multitenancy\Tasks\TasksCollection;

class ForgetCurrentTenantAction
{
    public function __construct(
        protected TasksCollection $tasksCollection
    ) {
    }

    public function execute(Tenant $tenant)
    {
        event(new ForgettingCurrentTenantEvent($tenant));

        $this
            ->performTaskToForgetCurrentTenant()
            ->clearBoundCurrentTenant();

        event(new ForgotCurrentTenantEvent($tenant));
    }

    protected function performTaskToForgetCurrentTenant(): self
    {
        $this->tasksCollection->each(fn (SwitchTenantTask $task) => $task->forgetCurrent());

        return $this;
    }

    protected function clearBoundCurrentTenant()
    {
        $containerKey = config('multitenancy.current_tenant_container_key');

        app()->forgetInstance($containerKey);
    }
}
