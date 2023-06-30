<?php

namespace Spatie\Multitenancy\Actions;

use Spatie\Multitenancy\Concerns\BindAsCurrentTenant;
use Spatie\Multitenancy\Events\MadeTenantCurrentEvent;
use Spatie\Multitenancy\Events\MakingTenantCurrentEvent;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tasks\SwitchTenantTask;
use Spatie\Multitenancy\Tasks\TasksCollection;

class MakeTenantCurrentAction
{
    use BindAsCurrentTenant;

    public function __construct(
        protected TasksCollection $tasksCollection
    ) {
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
}
