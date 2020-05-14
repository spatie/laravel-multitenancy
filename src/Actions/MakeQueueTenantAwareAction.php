<?php

namespace Spatie\Multitenancy\Actions;

use Illuminate\Queue\Events\JobProcessing;
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

            return Tenant::current()
                ? ['tenantId' => Tenant::current()->id]
                : [];
        });

        return $this;
    }

    protected function listenForJobsBeingProcessed(): self
    {
        app('events')->listen(JobProcessing::class, function (JobProcessing $event) {
            $tenantId = $event->job->payload()['tenantId'] ?? null;

            if (! $tenantId) {
                return;
            }

            if (! config('multitenancy.queues_are_tenant_aware_by_default')) {
                return;
            }
            /** @var \Spatie\Multitenancy\Models\Tenant $tenant */
            if (! $tenant = $this->getTenantModel()::find($event->job->payload()['tenantId'])) {
                return;
            }

            $tenant->makeCurrent();
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
}
