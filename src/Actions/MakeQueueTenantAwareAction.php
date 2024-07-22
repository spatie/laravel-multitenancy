<?php

namespace Spatie\Multitenancy\Actions;

use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobRetryRequested;
use Illuminate\Support\Facades\Context;
use Spatie\Multitenancy\Concerns\BindAsCurrentTenant;
use Spatie\Multitenancy\Concerns\UsesMultitenancyConfig;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\Exceptions\CurrentTenantCouldNotBeDeterminedInTenantAwareJob;
use Spatie\Multitenancy\Jobs\NotTenantAware;
use Spatie\Multitenancy\Jobs\TenantAware;

class MakeQueueTenantAwareAction
{
    use BindAsCurrentTenant;
    use UsesMultitenancyConfig;

    public function execute(): void
    {
        $this
            ->listenForJobsBeingProcessed()
            ->listenForJobsRetryRequested();
    }

    protected function listenForJobsBeingProcessed(): static
    {
        app('events')->listen(JobProcessing::class, function (JobProcessing $event) {
            $this->bindOrForgetCurrentTenant($event);
        });

        return $this;
    }

    protected function listenForJobsRetryRequested(): static
    {
        app('events')->listen(JobRetryRequested::class, function (JobRetryRequested $event) {
            $this->bindOrForgetCurrentTenant($event);
        });

        return $this;
    }

    protected function isTenantAware(JobProcessing|JobRetryRequested $event): bool
    {
        $jobName = $this->getEventPayload($event)['data']['commandName'];

        $reflection = new \ReflectionClass($jobName);

        if ($reflection->implementsInterface(TenantAware::class)) {
            return true;
        }

        if ($reflection->implementsInterface(NotTenantAware::class)) {
            return false;
        }

        if (in_array($reflection->name, config('multitenancy.tenant_aware_jobs'))) {
            return true;
        }

        if (in_array($reflection->name, config('multitenancy.not_tenant_aware_jobs'))) {
            return false;
        }

        return config('multitenancy.queues_are_tenant_aware_by_default') === true;
    }

    protected function getEventPayload($event): ?array
    {
        return match (true) {
            $event instanceof JobProcessing => $event->job->payload(),
            $event instanceof JobRetryRequested => $event->payload(),
            default => null,
        };
    }

    protected function findTenant(JobProcessing|JobRetryRequested $event): IsTenant
    {
        $tenantId = Context::get($this->currentTenantContextKey());

        if (! $tenantId) {
            $event->job->delete();

            throw CurrentTenantCouldNotBeDeterminedInTenantAwareJob::noIdSet($event);
        }

        if (! $tenant = app(IsTenant::class)::find($tenantId)) {
            $event->job->delete();

            throw CurrentTenantCouldNotBeDeterminedInTenantAwareJob::noTenantFound($event);
        }

        return $tenant;
    }

    protected function bindOrForgetCurrentTenant(JobProcessing|JobRetryRequested $event): void
    {
        if ($this->isTenantAware($event)) {
            $this->bindAsCurrentTenant($this->findTenant($event)->makeCurrent());

            return;
        }

        app(IsTenant::class)::forgetCurrent();
    }
}
