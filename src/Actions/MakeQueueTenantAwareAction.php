<?php

namespace Spatie\Multitenancy\Actions;

use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobProcessed;
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

    /**
     * Shared between instances, as an application that starts multitenancy more
     * than once, like Laravel Octane does for every request, ends up with a set
     * of these listeners per start. Popping in reverse registration order then
     * still leaves the tenant of the outermost listener current.
     *
     * @var array<int, ?IsTenant>
     */
    protected static array $tenantsCurrentBeforeJob = [];

    protected bool $listeningForJobsHavingBeenProcessed = false;

    public function execute(): void
    {
        $this
            ->listenForJobsBeingProcessed()
            ->listenForJobsRetryRequested();
    }

    protected function listenForJobsBeingProcessed(): static
    {
        app('events')->listen(JobProcessing::class, function (JobProcessing $event) {
            $this->listenForJobsHavingBeenProcessed();

            static::$tenantsCurrentBeforeJob[] = app(IsTenant::class)::current();

            $this->bindOrForgetCurrentTenant($event);
        });

        return $this;
    }

    /**
     * A job may run in the same process as the code that dispatched it, on the
     * `sync` connection or through `dispatchSync`. Processing it should not
     * leave the dispatching context with a different tenant than it had.
     *
     * Registering only once the first job starts processing keeps this listener
     * behind the ones the application registered while booting. Those still see
     * the tenant of the job they are handling.
     */
    protected function listenForJobsHavingBeenProcessed(): static
    {
        if ($this->listeningForJobsHavingBeenProcessed) {
            return $this;
        }

        $this->listeningForJobsHavingBeenProcessed = true;

        app('events')->listen([JobProcessed::class, JobExceptionOccurred::class], function () {
            $this->restoreTenantCurrentBeforeJob();
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

    protected function restoreTenantCurrentBeforeJob(): void
    {
        if (static::$tenantsCurrentBeforeJob === []) {
            return;
        }

        $tenant = array_pop(static::$tenantsCurrentBeforeJob);

        if (app(IsTenant::class)::current()?->getKey() === $tenant?->getKey()) {
            return;
        }

        if (! $tenant) {
            app(IsTenant::class)::forgetCurrent();

            return;
        }

        $tenant->makeCurrent();
    }

    protected function isTenantAware(JobProcessing|JobRetryRequested $event): bool
    {
        $payload = $this->getEventPayload($event);

        /**
         * Legacy string-job payloads ({"job":"Class@method","data":{...}}) - pushed by
         * something other than Laravel's dispatcher, e.g. AWS EventBridge - have no
         * serialized command. Resolve the class name straight from the payload instead.
         */
        if (! isset($payload['data']['command'])) {
            return $this->resolveTenantAwarenessForJob($this->jobClassFromLegacyPayload($payload));
        }

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
            if ($tenantId = $this->resolveTenantId($event)) {
                $tenant = app(IsTenant::class)::find($tenantId);
                $tenant?->makeCurrent();
            }

            $command = unserialize($serializedCommand);
        }

        return $this->resolveTenantAwarenessForJob($this->getJobFromQueueable($command));
    }

    /**
     * Determine tenant-awareness from a job's interface declarations and the
     * configured allow/deny lists, falling back to the package default. Accepts
     * either a job instance (modern payloads) or a class name (legacy payloads).
     */
    protected function resolveTenantAwarenessForJob(object|string|null $job): bool
    {
        if ($job === null) {
            return config('multitenancy.queues_are_tenant_aware_by_default') === true;
        }

        $reflection = new ReflectionClass($job);

        if ($reflection->implementsInterface(config('multitenancy.tenant_aware_interface', TenantAware::class))) {
            return true;
        }

        if ($reflection->implementsInterface(config('multitenancy.not_tenant_aware_interface', NotTenantAware::class))) {
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

    /**
     * Resolve the job class name from a legacy string-job payload's `job` key
     * (`Fully\Qualified\ClassName@method`), or null when it cannot be resolved.
     */
    protected function jobClassFromLegacyPayload(array $payload): ?string
    {
        $jobName = $payload['job'] ?? null;

        if (! is_string($jobName)) {
            return null;
        }

        $class = explode('@', $jobName)[0];

        return class_exists($class) ? $class : null;
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
        $tenantId = $this->resolveTenantId($event);

        if (! $tenantId) {
            if ($event instanceof JobProcessing) {
                $event->job->delete();
            }

            throw CurrentTenantCouldNotBeDeterminedInTenantAwareJob::noIdSet($event);
        }

        if (! $tenant = app(IsTenant::class)::find($tenantId)) {
            if ($event instanceof JobProcessing) {
                $event->job->delete();
            }

            throw CurrentTenantCouldNotBeDeterminedInTenantAwareJob::noTenantFound($event);
        }

        return $tenant;
    }

    protected function resolveTenantId(JobProcessing|JobRetryRequested $event): mixed
    {
        if ($event instanceof JobRetryRequested) {
            return $this->tenantIdFromPayloadContext($event);
        }

        return Context::get($this->currentTenantContextKey());
    }

    /**
     * When a job is retried through `queue:retry`, Laravel has not yet hydrated
     * the stored context onto the `Context` facade, so we read the tenant id
     * straight from the payload's serialized context instead.
     */
    protected function tenantIdFromPayloadContext(JobRetryRequested $event): mixed
    {
        $contextData = $this->getEventPayload($event)['illuminate:log:context']['data'] ?? [];

        $serializedTenantId = $contextData[$this->currentTenantContextKey()] ?? null;

        if ($serializedTenantId === null) {
            return null;
        }

        try {
            return unserialize($serializedTenantId);
        } catch (Throwable) {
            return null;
        }
    }

    protected function bindOrForgetCurrentTenant(JobProcessing|JobRetryRequested $event): void
    {
        if ($this->isTenantAware($event)) {
            $tenant = $this->findTenant($event);

            $tenant->makeCurrent();

            $this->bindAsCurrentTenant($tenant);

            return;
        }

        app(IsTenant::class)::forgetCurrent();
    }
}
