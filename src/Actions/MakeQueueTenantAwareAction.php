<?php declare(strict_types=1);

namespace Spatie\Multitenancy\Actions;

use Spatie\Multitenancy\Models\Tenant;
use Illuminate\Mail\SendQueuedMailable;
use Illuminate\Events\CallQueuedListener;
use Spatie\Multitenancy\Jobs\TenantAware;
use Illuminate\Queue\Events\JobProcessing;
use Spatie\Multitenancy\Jobs\NotTenantAware;
use Illuminate\Notifications\SendQueuedNotifications;
use Spatie\Multitenancy\Models\Concerns\UsesTenantModel;
use Spatie\Multitenancy\Exceptions\CurrentTenantCouldNotBeDeterminedInTenantAwareJob;

class MakeQueueTenantAwareAction
{
    use UsesTenantModel;

    public function execute(): void
    {
        $this
            ->listenForJobsBeingQueued()
            ->listenForJobsBeingProcessed();
    }

    protected function listenForJobsBeingQueued(): self
    {
        app('queue')->createPayloadUsing(function ($connectionName, $queue, $payload) {
            $queueable = $payload['data']['command'];

            if (! $this->isTenantAware($queueable)) {
                return [];
            }

            return ['tenantId' => optional(Tenant::current())->id];
        });

        return $this;
    }

    protected function listenForJobsBeingProcessed(): self
    {
        app('events')->listen(JobProcessing::class, function (JobProcessing $event) {
            if (! array_key_exists('tenantId', $event->job->payload())) {
                return;
            }

            $this->findTenant($event)->makeCurrent();
        });

        return $this;
    }

    protected function isTenantAware(object $queueable): bool
    {
        $reflection = new \ReflectionClass($this->getJobFromQueueable($queueable));

        if ($reflection->implementsInterface(TenantAware::class)) {
            return true;
        } elseif ($reflection->implementsInterface(NotTenantAware::class)) {
            return false;
        }

        return config('multitenancy.queues_are_tenant_aware_by_default') === true;
    }

    protected function findTenant(JobProcessing $event): Tenant
    {
        $tenantId = $event->job->payload()['tenantId'];

        if (! $tenantId) {
            $event->job->delete();

            throw CurrentTenantCouldNotBeDeterminedInTenantAwareJob::noIdSet($event);
        }

        /** @var Tenant $tenant */
        if (! $tenant = $this->getTenantModel()::find($tenantId)) {
            $event->job->delete();

            throw CurrentTenantCouldNotBeDeterminedInTenantAwareJob::noTenantFound($event);
        }

        return $tenant;
    }

    protected function getJobFromQueueable(object $queueable)
    {
        switch (get_class($queueable)) {
            case SendQueuedMailable::class:
                return $queueable->mailable;
            case SendQueuedNotifications::class:
                return $queueable->notification;
            case CallQueuedListener::class:
                return $queueable->class;
            default:
                return $queueable;
        }
    }
}
