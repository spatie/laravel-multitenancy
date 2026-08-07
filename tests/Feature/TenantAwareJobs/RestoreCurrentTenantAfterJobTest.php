<?php

use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Facades\Queue;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Multitenancy;
use Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses\DispatchesNestedJobTestJob;
use Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses\FailingNotTenantAwareTestJob;
use Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses\NotTenantAwareTestJob;
use Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses\TenantAwareTestJob;
use Spatie\Valuestore\Valuestore;

beforeEach(function () {
    config()->set('multitenancy.queues_are_tenant_aware_by_default', true);
    config()->set('queue.default', 'sync');
    config()->set('queue.failed.driver', 'null');

    $this->tenant = Tenant::factory()->create();

    $this->valuestore = Valuestore::make(tempFile('tenantAware.json'))->flush();
});

it('keeps the current tenant of the dispatching context after a sync not tenant aware job', function () {
    $this->tenant->makeCurrent();

    app(Dispatcher::class)->dispatch(new NotTenantAwareTestJob($this->valuestore));

    expect($this->valuestore->get('tenantId'))->toBeNull()
        ->and(Tenant::current()?->id)->toEqual($this->tenant->id);
});

it('keeps the current tenant of the dispatching context after a sync tenant aware job', function () {
    $this->tenant->makeCurrent();

    app(Dispatcher::class)->dispatch(new TenantAwareTestJob($this->valuestore));

    expect($this->valuestore->get('tenantId'))->toEqual($this->tenant->id)
        ->and(Tenant::current()?->id)->toEqual($this->tenant->id);
});

it('keeps the current tenant of the dispatching context after a sync job fails', function () {
    $this->tenant->makeCurrent();

    try {
        app(Dispatcher::class)->dispatch(new FailingNotTenantAwareTestJob($this->valuestore));
    } catch (Exception) {
    }

    expect(Tenant::current()?->id)->toEqual($this->tenant->id);
});

it('restores the current tenant of every level of nested sync jobs', function () {
    $this->tenant->makeCurrent();

    app(Dispatcher::class)->dispatch(new DispatchesNestedJobTestJob($this->valuestore));

    expect($this->valuestore->get('tenantId'))->toBeNull()
        ->and($this->valuestore->get('tenantIdAfterNestedJob'))->toEqual($this->tenant->id)
        ->and(Tenant::current()?->id)->toEqual($this->tenant->id);
});

it('does not leave a tenant current when none was current before the job', function () {
    config()->set('queue.default', 'database');

    $this->tenant->makeCurrent();

    app(Dispatcher::class)->dispatch(new TenantAwareTestJob($this->valuestore));

    Tenant::forgetCurrent();

    $this->artisan('queue:work --once')->assertExitCode(0);

    expect($this->valuestore->get('tenantId'))->toEqual($this->tenant->id)
        ->and(Tenant::checkCurrent())->toBeFalse();
});

it('still restores the tenant when multitenancy was started more than once', function () {
    app(Multitenancy::class)->start();

    $this->tenant->makeCurrent();

    app(Dispatcher::class)->dispatch(new NotTenantAwareTestJob($this->valuestore));

    expect($this->valuestore->get('tenantId'))->toBeNull()
        ->and(Tenant::current()?->id)->toEqual($this->tenant->id);
});

it('restores the tenant only after the callbacks of the application have run', function () {
    config()->set('queue.default', 'database');

    $tenantInCallback = false;

    Queue::after(function () use (&$tenantInCallback) {
        $tenantInCallback = Tenant::current()?->id;
    });

    $this->tenant->makeCurrent();

    app(Dispatcher::class)->dispatch(new TenantAwareTestJob($this->valuestore));

    Tenant::forgetCurrent();

    $this->artisan('queue:work --once')->assertExitCode(0);

    expect($tenantInCallback)->toEqual($this->tenant->id)
        ->and(Tenant::checkCurrent())->toBeFalse();
});

it('restores the tenant of the dispatching context after a job of another tenant was processed', function () {
    config()->set('queue.default', 'database');

    $otherTenant = Tenant::factory()->create();

    $otherTenant->makeCurrent();

    app(Dispatcher::class)->dispatch(new TenantAwareTestJob($this->valuestore));

    $this->tenant->makeCurrent();

    $this->artisan('queue:work --once')->assertExitCode(0);

    expect($this->valuestore->get('tenantId'))->toEqual($otherTenant->id)
        ->and(Tenant::current()?->id)->toEqual($this->tenant->id);
});
