<?php

use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses\EncryptedTenantAware;
use Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses\NotTenantAwareTestJob;
use Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses\TenantAwareTestJob;
use Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses\TestJob;
use Spatie\Valuestore\Valuestore;

beforeEach(function () {
    Event::fake(JobFailed::class);

    config()->set('multitenancy.queues_are_tenant_aware_by_default', true);

    $this->tenant = Tenant::factory()->create();

    $this->valuestore = Valuestore::make(tempFile('tenantAware.json'))->flush();

    Event::assertNotDispatched(JobFailed::class);
});

it('will inject the current tenant id in a job', function () {
    $this->tenant->makeCurrent();

    $job = new TestJob($this->valuestore);
    app(Dispatcher::class)->dispatch($job);

    Tenant::forgetCurrent();

    $this->artisan('queue:work --once')->assertExitCode(0);

    $currentTenantIdInJob = $this->valuestore->get('tenantId');

    expect($this->valuestore->get('tenantIdInContext'))->toBe($this->tenant->getKey())
        ->and($this->tenant->id)->toEqual($currentTenantIdInJob);
});

it('will inject the right tenant even when the current tenant switches', function () {
    /** @var \Spatie\Multitenancy\Models\Tenant $anotherTenant */
    $anotherTenant = Tenant::factory()->create();

    $this->tenant->makeCurrent();
    $job = new TestJob($this->valuestore);
    app(Dispatcher::class)->dispatch($job);

    $this->artisan('queue:work --once');

    $currentTenantIdInJob = $this->valuestore->get('tenantId');
    expect($this->tenant->id)->toEqual($currentTenantIdInJob);

    $anotherTenant->makeCurrent();
    $job = new TestJob($this->valuestore);
    app(Dispatcher::class)->dispatch($job);

    $this->artisan('queue:work --once');

    $currentTenantIdInJob = $this->valuestore->get('tenantId');

    expect($anotherTenant->id)->toEqual($currentTenantIdInJob);
});

it('will not make jobs tenant aware if the config settings is set to false', function () {
    config()->set('multitenancy.queues_are_tenant_aware_by_default', false);

    $this->tenant->makeCurrent();

    $job = new TestJob($this->valuestore);
    app(Dispatcher::class)->dispatch($job);

    $this->artisan('queue:work --once')->assertExitCode(0);

    $currentTenantIdInJob = $this->valuestore->get('tenantId');
    expect($currentTenantIdInJob)->toBeNull();
});

it('will always make jobs tenant aware if they implement the TenantAware interface', function () {
    config()->set('multitenancy.queues_are_tenant_aware_by_default', false);

    $this->tenant->makeCurrent();

    $job = new TenantAwareTestJob($this->valuestore);
    app(Dispatcher::class)->dispatch($job);

    $this->artisan('queue:work --once')->assertExitCode(0);

    $currentTenantIdInJob = $this->valuestore->get('tenantId');
    expect($this->tenant->id)->toEqual($currentTenantIdInJob);
});

it('will not make a job tenant aware if it implements NotTenantAware', function () {
    config()->set('multitenancy.queues_are_tenant_aware_by_default', true);

    $this->tenant->makeCurrent();

    $job = new NotTenantAwareTestJob($this->valuestore);
    app(Dispatcher::class)->dispatch($job);

    $this->artisan('queue:work --once')->assertExitCode(0);

    $currentTenantIdInJob = $this->valuestore->get('tenantId');
    expect($currentTenantIdInJob)->toBeNull();
});

it('will decrypt encrypted jobs', function () {
    config()->set('multitenancy.queues_are_tenant_aware_by_default', true);

    $this->tenant->makeCurrent();

    $job = new EncryptedTenantAware($this->valuestore);
    app(Dispatcher::class)->dispatch($job);

    $this->artisan('queue:work --once')->assertExitCode(0);

    Event::assertNotDispatched(JobFailed::class);
});
