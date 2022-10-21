<?php

namespace Spatie\Multitenancy\Tests\Feature\TenantAwareJobs;

use Illuminate\Contracts\Bus\Dispatcher;
use Spatie\Multitenancy\Exceptions\CurrentTenantCouldNotBeDeterminedInTenantAwareJob;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses\NotTenantAwareTestJob;
use Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses\TenantAwareTestJob;
use Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses\TestJob;
use Spatie\Multitenancy\Tests\TestCase;
use Spatie\Valuestore\Valuestore;

beforeEach(function () {
    config()->set('multitenancy.queues_are_tenant_aware_by_default', true);
    config()->set('queue.default', 'sync');

    $this->tenant = Tenant::factory()->create();

    $this->valuestore = Valuestore::make($this->tempFile('tenantAware.json'))->flush();
});

test('it will fail a job when no tenant is present and queues are tenant aware by default', function () {
    config()->set('multitenancy.queues_are_tenant_aware_by_default', true);

    $job = new TestJob($this->valuestore);

    try {
        app(Dispatcher::class)->dispatch($job);
    } catch (CurrentTenantCouldNotBeDeterminedInTenantAwareJob $exception) {
        // Assert the job did not run
        $this->assertFalse($this->valuestore->has('tenantId'));

        return;
    }

    $this->fail();
});

test('it will fail a job when no tenant is present and job implements the TenantAware interface', function () {
    config()->set('multitenancy.queues_are_tenant_aware_by_default', false);

    $job = new TenantAwareTestJob($this->valuestore);

    try {
        app(Dispatcher::class)->dispatch($job);
    } catch (CurrentTenantCouldNotBeDeterminedInTenantAwareJob $exception) {
        $this->assertFalse($this->valuestore->has('tenantId'));

        return;
    }

    $this->fail();
});

test('it will not fail a job when no tenant is present and queues are not tenant aware by default', function () {
    config()->set('multitenancy.queues_are_tenant_aware_by_default', false);

    $job = new TestJob($this->valuestore);

    app(Dispatcher::class)->dispatch($job);

    $this->assertTrue($this->valuestore->has('tenantId'));
    $this->assertNull($this->valuestore->get('tenantId'));
});

test('it will not fail a job when no tenant is present and job implements the NotTenantAware interface', function () {
    config()->set('multitenancy.queues_are_tenant_aware_by_default', true);

    $job = new NotTenantAwareTestJob($this->valuestore);

    app(Dispatcher::class)->dispatch($job);

    $this->assertTrue($this->valuestore->has('tenantId'));
    $this->assertNull($this->valuestore->get('tenantId'));
});

test('it will not touch the tenant if the job is not tenant aware', function () {
    $this->tenant->makeCurrent();

    $job = new NotTenantAwareTestJob($this->valuestore);

    // Simulate a tenant being set from a previous queue job
    $this->assertTrue(Tenant::checkCurrent());

    app(Dispatcher::class)->dispatch($job);

    // Assert that the active tenant was not modified
    $this->assertSame($this->tenant->id, Tenant::current()->id);
});
