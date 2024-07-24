<?php

namespace Spatie\Multitenancy\Actions;

use Illuminate\Support\Facades\Context;
use Spatie\Multitenancy\Concerns\UsesMultitenancyConfig;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\Events\ForgettingCurrentTenantEvent;
use Spatie\Multitenancy\Events\ForgotCurrentTenantEvent;
use Spatie\Multitenancy\Tasks\SwitchTenantTask;
use Spatie\Multitenancy\Tasks\TasksCollection;

class ForgetCurrentTenantAction
{
    use UsesMultitenancyConfig;

    public function __construct(
        protected TasksCollection $tasksCollection
    ) {
    }

    public function execute(IsTenant $tenant): void
    {
        event(new ForgettingCurrentTenantEvent($tenant));

        $this
            ->performTaskToForgetCurrentTenant()
            ->clearBoundCurrentTenant();

        event(new ForgotCurrentTenantEvent($tenant));
    }

    protected function performTaskToForgetCurrentTenant(): static
    {
        $this->tasksCollection->each(fn (SwitchTenantTask $task) => $task->forgetCurrent());

        return $this;
    }

    protected function clearBoundCurrentTenant(): void
    {
        app()->forgetInstance($this->currentTenantContainerKey());

        Context::forget($this->currentTenantContextKey());
    }
}
