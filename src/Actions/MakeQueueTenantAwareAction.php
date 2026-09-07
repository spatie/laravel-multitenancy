<?php

namespace Spatie\Multitenancy\Actions;

use Illuminate\Console\Events\CommandFinished;
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

    /**
     * Stack storing tenant context state before jobs are processed.
     *
     * @var array<int, array{tenant: ?IsTenant, context: mixed}>
     */
    protected static array $tenantContextStack = [];

    /**
     * Holds at most one tenant: the one that was current when `queue:retry`
     * started pushing failed jobs back onto the queue.
     *
     * @var array<int, ?IsTenant>
     */
    protected static array $tenantsCurrentBeforeRetrying = [];

    protected bool $listeningForJobsBeingQueued = false;

    protected bool $listeningForJobsHavingBeenProcessed = false;

    protected bool $listeningForTheRetryCommandHavingFinished = false;

    public static function resetState(): void
    {
        static::$tenantsCurrentBeforeJob = [];
        static::$tenantContextStack = [];
        static::$tenantsCurrentBeforeRetrying = [];
    }

    public function execute(): void
    {
        $this
            ->listenForJobsBeingQueued()
            ->listenForJobsBeingProcessed()
            ->listenForJobsRetryRequested();
    }

    protected function listenForJobsBeingQueued(): static
    {
        if (class_exists(Context::class)) {
            return $this;
        }

        if ($this->listeningForJobsBeingQueued) {
            return $this;
        }

        $this->listeningForJobsBeingQueued = true;

        app('queue')->createPayloadUsing(function ($connectionName, $queue, $payload) {
            $queueable = data_get($payload, 'data.command', $this->jobClassFromLegacyPayload($payload ?? []));

            if (! $this->isQueueableTenantAware($queueable)) {
                return [];
            }

            return ['tenantId' => app(IsTenant::class)::current()?->getKey()];
        });

        return $this;
    }

    protected function isQueueableTenantAware(mixed $queueable): bool
    {
        if ($queueable === null) {
            return config('multitenancy.queues_are_tenant_aware_by_default') === true;
        }

        if (is_string($queueable)) {
            if (! str_starts_with($queueable, 'O:') && ! str_starts_with($queueable, 'a:') && ! str_starts_with($queueable, 'C:') && ! str_starts_with($queueable, 's:')) {
                try {
                    $queueable = app(Encrypter::class)->decrypt($queueable);
                } catch (Throwable) {
                }
            }

            try {
                $queueable = unserialize($queueable);
            } catch (Throwable) {
                return $this->resolveTenantAwarenessForJob($queueable);
            }
        }

        $job = is_object($queueable) ? $this->getJobFromQueueable($queueable) : $queueable;

        return $this->resolveTenantAwarenessForJob($job);
    }

    protected function listenForJobsBeingProcessed(): static
    {
        app('events')->listen(JobProcessing::class, function (JobProcessing $event) {
            $this->listenForJobsHavingBeenProcessed();

            $currentTenant = app(IsTenant::class)::current();
            $currentContext = class_exists(Context::class) ? Context::get($this->currentTenantContextKey()) : null;

            static::$tenantsCurrentBeforeJob[] = $currentTenant;
            static::$tenantContextStack[] = [
                'tenant' => $currentTenant,
                'context' => $currentContext,
            ];

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
    protected function listenForJobsHavingBeenProcessed(): void
    {
        if ($this->listeningForJobsHavingBeenProcessed) {
            return;
        }

        $this->listeningForJobsHavingBeenProcessed = true;

        app('events')->listen([JobProcessed::class, JobExceptionOccurred::class], function () {
            if (static::$tenantsCurrentBeforeJob === [] && static::$tenantContextStack === []) {
                return;
            }

            $previousTenant = static::$tenantsCurrentBeforeJob !== []
                ? array_pop(static::$tenantsCurrentBeforeJob)
                : null;

            $previousContext = null;
            if (static::$tenantContextStack !== []) {
                $stackItem = array_pop(static::$tenantContextStack);
                $previousTenant = $stackItem['tenant'] ?? $previousTenant;
                $previousContext = $stackItem['context'] ?? null;
            }

            $this->makeTenantCurrentAgain($previousTenant);

            if (class_exists(Context::class)) {
                if ($previousContext !== null) {
                    Context::add($this->currentTenantContextKey(), $previousContext);
                } else {
                    Context::forget($this->currentTenantContextKey());
                }
            }
        });
    }

    protected function listenForJobsRetryRequested(): static
    {
        app('events')->listen(JobRetryRequested::class, function (JobRetryRequested $event) {
            $this->listenForTheRetryCommandHavingFinished();

            if (static::$tenantsCurrentBeforeRetrying === []) {
                static::$tenantsCurrentBeforeRetrying[] = app(IsTenant::class)::current();
            }

            $this->bindOrForgetCurrentTenant($event);
        });

        return $this;
    }

    /**
     * `queue:retry` needs the tenant of a failed job to stay current after this
     * event, as it reads the payload again to push the job back onto the queue.
     * Only when the command is done can the tenant of whatever started it, an
     * `Artisan::call('queue:retry')` in a request for instance, be put back.
     */
    protected function listenForTheRetryCommandHavingFinished(): void
    {
        if ($this->listeningForTheRetryCommandHavingFinished) {
            return;
        }

        $this->listeningForTheRetryCommandHavingFinished = true;

        app('events')->listen(CommandFinished::class, function () {
            if (static::$tenantsCurrentBeforeRetrying === []) {
                return;
            }

            $this->makeTenantCurrentAgain(array_pop(static::$tenantsCurrentBeforeRetrying));
        });
    }

    protected function makeTenantCurrentAgain(?IsTenant $tenant): void
    {
        /**
         * Comparing keys here instead of leaning on `makeCurrent`, which skips
         * its work when the given tenant already is the current one. It reaches
         * that conclusion through `current()`, typed as `?static`, which throws
         * when the tenant to restore is of another class than the one the job
         * bound, a subclass of the tenant model for instance.
         */
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
            return $this->resolveTenantAwarenessForJob($this->jobClassFromLegacyPayload($payload ?? []));
        }

        $serializedCommand = $payload['data']['command'];

        if (is_object($serializedCommand)) {
            return $this->resolveTenantAwarenessForJob($this->getJobFromQueueable($serializedCommand));
        }

        if (! is_string($serializedCommand)) {
            return config('multitenancy.queues_are_tenant_aware_by_default') === true;
        }

        if (! str_starts_with($serializedCommand, 'O:') && ! str_starts_with($serializedCommand, 'a:') && ! str_starts_with($serializedCommand, 's:') && ! str_starts_with($serializedCommand, 'C:')) {
            try {
                $serializedCommand = app(Encrypter::class)->decrypt($serializedCommand);
            } catch (Throwable) {
            }
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

            try {
                $command = unserialize($serializedCommand);
            } catch (Throwable) {
                return config('multitenancy.queues_are_tenant_aware_by_default') === true;
            }
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

        if (in_array($reflection->name, config('multitenancy.tenant_aware_jobs') ?? [], true)) {
            return true;
        }

        if (in_array($reflection->name, config('multitenancy.not_tenant_aware_jobs') ?? [], true)) {
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
        $job = Arr::get(config('multitenancy.queueable_to_job') ?? [], $queueable::class);

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

        if (class_exists(Context::class) && ($contextId = Context::get($this->currentTenantContextKey()))) {
            return $contextId;
        }

        $payload = $this->getEventPayload($event);

        if (isset($payload['tenantId'])) {
            return $payload['tenantId'];
        }

        if (isset($payload['data'][$this->currentTenantContextKey()])) {
            return $payload['data'][$this->currentTenantContextKey()];
        }

        return $this->tenantIdFromPayloadContext($event);
    }



    /**
     * When a job is retried through `queue:retry`, Laravel has not yet hydrated
     * the stored context onto the `Context` facade, so we read the tenant id
     * straight from the payload's serialized context instead.
     */
    protected function tenantIdFromPayloadContext(JobProcessing|JobRetryRequested $event): mixed
    {
        $contextData = $this->getEventPayload($event)['illuminate:log:context']['data'] ?? [];

        $serializedTenantId = $contextData[$this->currentTenantContextKey()] ?? null;

        if ($serializedTenantId === null) {
            return null;
        }

        try {
            return unserialize($serializedTenantId);
        } catch (Throwable) {
            return $serializedTenantId;
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
