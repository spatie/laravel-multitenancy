<?php

namespace Spatie\Multitenancy\Actions;

use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tasks\TasksCollection;
use Spatie\Multitenancy\Tasks\SwitchTenantTask;
use Spatie\Multitenancy\Events\ForgotCurrentTenantEvent;
use Spatie\Multitenancy\Events\ForgettingCurrentTenantEvent;

class ForgetCurrentTenantAction
{
    private TasksCollection $tasksCollection;

    public function __construct(TasksCollection $tasksCollection)
    {
        $this->tasksCollection = $tasksCollection;
    }

    public function execute(Tenant $tenant): void
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

    private function clearBoundCurrentTenant(): void
    {
        $containerKey = config('multitenancy.current_tenant_container_key');

        app()->forgetInstance($containerKey);
    }
}
