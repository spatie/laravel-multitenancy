<?php

namespace Spatie\Multitenancy\Actions;

use Spatie\Multitenancy\Events\MadeTenantCurrentEvent;
use Spatie\Multitenancy\Events\MakingTenantCurrentEvent;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tasks\SwitchTenantTask;
use Spatie\Multitenancy\Tasks\TasksCollection;

class MakeTenantCurrentAction
{
    protected TasksCollection $tasksCollection;

    public function __construct(TasksCollection $tasksCollection)
    {
        $this->tasksCollection = $tasksCollection;
    }

    public function execute(Tenant $tenant)
    {
        event(new MakingTenantCurrentEvent($tenant));

        $this
            ->performTasksToMakeTenantCurrent($tenant)
            ->bindAsCurrentTenant($tenant);

        event(new MadeTenantCurrentEvent($tenant));

        return $this;
    }

    protected function performTasksToMakeTenantCurrent(Tenant $tenant): self
    {
        $this->tasksCollection->each(fn (SwitchTenantTask $task) => $task->makeCurrent($tenant));

        return $this;
    }

    protected function bindAsCurrentTenant(Tenant $tenant): self
    {
        $containerKey = config('multitenancy.current_tenant_container_key');

        app()->forgetInstance($containerKey);

        app()->instance($containerKey, $tenant);

        return $this;
    }
}
