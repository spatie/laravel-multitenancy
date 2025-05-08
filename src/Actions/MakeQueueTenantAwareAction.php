<?php

namespace Spatie\Multitenancy\Actions;

use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobRetryRequested;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Context;
use ReflectionClass;
use Spatie\Multitenancy\Concerns\BindAsCurrentTenant;
use Spatie\Multitenancy\Concerns\UsesMultitenancyConfig;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\Exceptions\CurrentTenantCouldNotBeDeterminedInTenantAwareJob;
use Spatie\Multitenancy\Jobs\NotTenantAware;
use Spatie\Multitenancy\Jobs\TenantAware;
use Throwable;

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
        $payload = $this->getEventPayload($event);

        $serializedCommand = $payload['data']['command'];

        if (! str_starts_with($serializedCommand, 'O:')) {
            $serializedCommand = app(Encrypter::class)->decrypt($serializedCommand);
        }

        try {
            $command = unserialize($serializedCommand);
        } catch (Throwable) {
            /**
             * We might need the tenant to unserialize jobs as models could
             * have global scopes set that require a current tenant to
             * be active. bindOrForgetCurrentTenant wil reset it.
             */
            if ($tenantId = Context::get($this->currentTenantContextKey())) {
                $tenant = app(IsTenant::class)::find($tenantId);
                $tenant?->makeCurrent();
            }

            $command = unserialize($serializedCommand);
        }

        $job = $this->getJobFromQueueable($command);

        $reflection = new ReflectionClass($job);

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

    protected function getJobFromQueueable(object $queueable)
    {
        $job = Arr::get(config('multitenancy.queueable_to_job'), $queueable::class);

        if (! $job) {
            return $queueable;
        }

        if (method_exists($queueable, $job)) {
            return $queueable->{$job}();
        }

        return $queueable->$job;
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
