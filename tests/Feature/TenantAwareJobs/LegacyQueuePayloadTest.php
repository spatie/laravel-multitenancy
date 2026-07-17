<?php

use Illuminate\Contracts\Queue\Job as QueueJobContract;
use Illuminate\Queue\Events\JobProcessing;
use Spatie\Multitenancy\Actions\MakeQueueTenantAwareAction;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses\NotTenantAwareTestJob;
use Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses\TenantAwareTestJob;
use Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses\TestJob;
use Spatie\Valuestore\Valuestore;

beforeEach(function () {
    config()->set('multitenancy.queues_are_tenant_aware_by_default', false);

    $this->tenant = Tenant::factory()->create();
});

/*
 * Legacy string-job payloads ({"job":"Class@method","data":{...}}) are produced when
 * messages are pushed to the queue by something other than Laravel's dispatcher
 * (e.g. AWS EventBridge input transformers). They carry no serialized `data.command`,
 * which used to make isTenantAware() throw "Undefined array key 'command'".
 */
function isTenantAwareForPayload(array $payload): bool
{
    $job = Mockery::mock(QueueJobContract::class);
    $job->allows('payload')->andReturn($payload);

    $action = new class () extends MakeQueueTenantAwareAction {
        public function determine(JobProcessing $event): bool
        {
            return $this->isTenantAware($event);
        }
    };

    return $action->determine(new JobProcessing('database', $job));
}

function legacyPayload(string $jobName): array
{
    return ['job' => $jobName, 'data' => []];
}

it('treats a legacy payload as not tenant aware when the job implements NotTenantAware', function () {
    $isTenantAware = isTenantAwareForPayload(legacyPayload(NotTenantAwareTestJob::class . '@handle'));

    expect($isTenantAware)->toBeFalse();
});

it('treats a legacy payload as tenant aware when the job implements TenantAware', function () {
    $isTenantAware = isTenantAwareForPayload(legacyPayload(TenantAwareTestJob::class . '@handle'));

    expect($isTenantAware)->toBeTrue();
});

it('treats a legacy payload as tenant aware when the job is in the tenant_aware_jobs config', function () {
    config()->set('multitenancy.tenant_aware_jobs', [TestJob::class]);

    $isTenantAware = isTenantAwareForPayload(legacyPayload(TestJob::class . '@handle'));

    expect($isTenantAware)->toBeTrue();
});

it('treats a legacy payload as not tenant aware when the job is in the not_tenant_aware_jobs config', function () {
    config()->set('multitenancy.queues_are_tenant_aware_by_default', true);
    config()->set('multitenancy.not_tenant_aware_jobs', [TestJob::class]);

    $isTenantAware = isTenantAwareForPayload(legacyPayload(TestJob::class . '@handle'));

    expect($isTenantAware)->toBeFalse();
});

it('falls back to the default (true) when the legacy job class cannot be resolved', function () {
    config()->set('multitenancy.queues_are_tenant_aware_by_default', true);

    $isTenantAware = isTenantAwareForPayload(legacyPayload('App\\Jobs\\SomeMissingClass@handle'));

    expect($isTenantAware)->toBeTrue();
});

it('falls back to the default (false) when the legacy job class cannot be resolved', function () {
    config()->set('multitenancy.queues_are_tenant_aware_by_default', false);

    $isTenantAware = isTenantAwareForPayload(legacyPayload('App\\Jobs\\SomeMissingClass@handle'));

    expect($isTenantAware)->toBeFalse();
});

it('falls back to the default when the legacy payload has no job key', function () {
    config()->set('multitenancy.queues_are_tenant_aware_by_default', true);

    $isTenantAware = isTenantAwareForPayload(['data' => []]);

    expect($isTenantAware)->toBeTrue();
});

it('leaves modern data.command payloads unchanged', function () {
    // With the default on, a modern job is only classified from its serialized command in
    // data.command. Its top-level `job` is Illuminate\Queue\CallQueuedHandler@call - never
    // the job class - so if the legacy branch hijacked modern payloads this would flip to true.
    config()->set('multitenancy.queues_are_tenant_aware_by_default', true);

    $valuestore = Valuestore::make(tempFile('tenantAware.json'))->flush();

    $isTenantAware = isTenantAwareForPayload([
        'job' => 'Illuminate\\Queue\\CallQueuedHandler@call',
        'data' => [
            'commandName' => NotTenantAwareTestJob::class,
            'command' => serialize(new NotTenantAwareTestJob($valuestore)),
        ],
    ]);

    expect($isTenantAware)->toBeFalse();
});

it('no longer throws when the real event path processes a legacy payload', function () {
    // Regression for the original crash: firing JobProcessing for a legacy NotTenantAware
    // payload used to throw "Undefined array key 'command'" before the interface was read.
    $this->tenant->makeCurrent();

    $job = Mockery::mock(QueueJobContract::class);
    $job->allows('payload')->andReturn(legacyPayload(NotTenantAwareTestJob::class . '@handle'));
    $job->allows('delete');

    event(new JobProcessing('database', $job));

    expect(Tenant::current())->toBeNull();
});
