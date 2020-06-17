<?php

namespace Spatie\Multitenancy\Actions;

use Illuminate\Queue\Events\JobProcessing;
use Spatie\Multitenancy\Exceptions\CurrentTenantCouldNotBeDeterminedInTenantAwareJob;
use Spatie\Multitenancy\Jobs\NotTenantAware;
use Spatie\Multitenancy\Jobs\TenantAware;
use Spatie\Multitenancy\Models\Concerns\UsesTenantModel;
use Spatie\Multitenancy\Models\Tenant;

class MakeQueueTenantAwareAction
{
    use UsesTenantModel;

    public function execute()
    {
        $this
            ->listenForJobsBeingQueued()
            ->listenForJobsBeingProcessed();
    }

    protected function listenForJobsBeingQueued(): self
    {
        app('queue')->createPayloadUsing(function ($connectionName, $queue, $payload) {
            $job = $payload['data']['command'];

            if (! $this->isTenantAware($job)) {
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

    protected function isTenantAware(object $job): bool
    {
        if ($job instanceof TenantAware) {
            return true;
        }

        if (config('multitenancy.queues_are_tenant_aware_by_default')) {
            if ($job instanceof NotTenantAware) {
                return false;
            }
        }

        if (! config('multitenancy.queues_are_tenant_aware_by_default')) {
            if ($job instanceof TenantAware) {
                return true;
            }

            return false;
        }

        return true;
    }

    protected function findTenant(JobProcessing $event): Tenant
    {
        $tenantId = $event->job->payload()['tenantId'];

        if (! $tenantId) {
            $event->job->delete();

            throw CurrentTenantCouldNotBeDeterminedInTenantAwareJob::noIdSet($event);
        }


        /** @var \Spatie\Multitenancy\Models\Tenant $tenant */
        if (! $tenant = $this->getTenantModel()::find($tenantId)) {
            $event->job->delete();

            throw CurrentTenantCouldNotBeDeterminedInTenantAwareJob::noTenantFound($event);
        }

        return $tenant;
    }
}
