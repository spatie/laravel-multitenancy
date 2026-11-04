<?php

use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Spatie\Multitenancy\Actions\MakeQueueTenantAwareAction;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses\EncryptedModelRestoringTestJob;
use Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses\ModelRestoringTestJob;
use Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses\NonDeletingModelRestoringTestJob;
use Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses\NotTenantAwareTestJob;
use Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses\TenantAwareTestJob;
use Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses\TestJob;
use Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses\WrappedTestJob;
use Spatie\Valuestore\Valuestore;

beforeEach(function () {
    config()->set('queue.failed.driver', 'null');

    $this->tenant = Tenant::factory()->create();
    $this->valuestore = Valuestore::make(tempFile('tenantAware.json'))->flush();
});

it('honors Laravel model restoration and missing model deletion policy', function (string $jobClass, bool $tenantAware, bool $missing, bool $delete) {
    config()->set('multitenancy.queues_are_tenant_aware_by_default', $tenantAware);

    $model = Tenant::factory()->create();
    $this->tenant->makeCurrent();

    app(Dispatcher::class)->dispatch(new $jobClass($this->valuestore, $model));

    if ($missing) {
        $model->delete();
    }

    Tenant::forgetCurrent();

    $failures = [];
    $exceptions = [];
    Event::listen(JobFailed::class, function (JobFailed $event) use (&$failures) {
        $failures[] = $event->exception::class;
    });
    Event::listen(JobExceptionOccurred::class, function (JobExceptionOccurred $event) use (&$exceptions) {
        $exceptions[] = $event->exception::class;
    });

    $queueSizes = [];

    for ($attempt = 0; $attempt < 3; $attempt++) {
        $this->artisan('queue:work --once --tries=3 --sleep=0')->assertExitCode(0);
        $queueSizes[] = Queue::size();
    }

    $expectedErrors = $missing && ! $delete ? [ModelNotFoundException::class] : [];

    expect([
        'queueSizes' => $queueSizes,
        'failures' => $failures,
        'exceptions' => $exceptions,
    ])->toBe([
        'queueSizes' => [0, 0, 0],
        'failures' => $expectedErrors,
        'exceptions' => $expectedErrors,
    ])->and($this->valuestore->has('tenantId'))->toBe(! $missing);

    if (! $missing) {
        expect($this->valuestore->get('tenantId'))->toBe($tenantAware ? $this->tenant->id : null);
    }
})->with([
    'tenant aware missing' => [ModelRestoringTestJob::class, true, true, true],
    'not tenant aware missing' => [ModelRestoringTestJob::class, false, true, true],
    'encrypted missing' => [EncryptedModelRestoringTestJob::class, true, true, true],
    'existing model' => [ModelRestoringTestJob::class, true, false, true],
    'existing non tenant model' => [ModelRestoringTestJob::class, false, false, true],
    'missing without deletion flag' => [NonDeletingModelRestoringTestJob::class, true, true, false],
]);

it('uses the configured wrapper target rather than its display name', function (string $target, string $accessor, ?string $displayName, bool $default, bool $tenantAware) {
    config()->set('multitenancy.queues_are_tenant_aware_by_default', $default);
    config()->set('multitenancy.queueable_to_job.' . WrappedTestJob::class, $accessor);

    $this->tenant->makeCurrent();
    app(Dispatcher::class)->dispatch(new WrappedTestJob(new $target($this->valuestore), $displayName));
    $otherTenant = Tenant::factory()->create();
    $otherTenant->makeCurrent();

    $this->artisan('queue:work --once')->assertExitCode(0);

    expect($this->valuestore->has('tenantId'))->toBeTrue()
        ->and($this->valuestore->get('tenantId'))->toBe($tenantAware ? $this->tenant->id : null)
        ->and(Tenant::current()?->id)->toBe($otherTenant->id);
})->with([
    'property' => [TenantAwareTestJob::class, 'wrapped', null, false, true],
    'method' => [TenantAwareTestJob::class, 'getJob', null, false, true],
    'not aware' => [NotTenantAwareTestJob::class, 'wrapped', null, true, false],
    'different valid class name' => [NotTenantAwareTestJob::class, 'wrapped', TenantAwareTestJob::class, true, false],
    'non class name control' => [TenantAwareTestJob::class, 'wrapped', 'Send digest', false, true],
]);

it('honors custom queueable resolution in an action subclass', function () {
    config()->set('multitenancy.queues_are_tenant_aware_by_default', false);

    (new class () extends MakeQueueTenantAwareAction {
        protected function getJobFromQueueable(object $queueable)
        {
            return $queueable instanceof WrappedTestJob ? $queueable->wrapped : $queueable;
        }
    })->execute();

    $this->tenant->makeCurrent();
    app(Dispatcher::class)->dispatch(new WrappedTestJob(new TenantAwareTestJob($this->valuestore)));
    Tenant::forgetCurrent();

    $this->artisan('queue:work --once')->assertExitCode(0);

    expect($this->valuestore->get('tenantId'))->toBe($this->tenant->id);
});

it('passes modern job instances to a custom awareness resolver', function () {
    config()->set('multitenancy.queues_are_tenant_aware_by_default', false);

    (new class () extends MakeQueueTenantAwareAction {
        protected function resolveTenantAwarenessForJob(object|string|null $job): bool
        {
            return $job instanceof TestJob;
        }
    })->execute();

    $this->tenant->makeCurrent();
    app(Dispatcher::class)->dispatch(new TestJob($this->valuestore));
    Tenant::forgetCurrent();

    $this->artisan('queue:work --once')->assertExitCode(0);

    expect($this->valuestore->get('tenantId'))->toBe($this->tenant->id);
});
