<?php

use Illuminate\Support\Facades\Event;
use Spatie\Multitenancy\Exceptions\CurrentTenantCouldNotBeDeterminedInTenantAwareJob;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses\ListenerNotTenantAware;
use Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses\ListenerTenantAware;
use Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses\TestEvent;

beforeEach(function () {
    config()->set('multitenancy.queues_are_tenant_aware_by_default', true);
    config()->set('queue.default', 'sync');
    config()->set('mail.default', 'log');

    $this->tenant = Tenant::factory()->create();
});

it('will fail when no tenant is present and listeners are tenant aware by default', function () {
    config()->set('multitenancy.queues_are_tenant_aware_by_default', true);

    Event::listen(TestEvent::class, ListenerTenantAware::class);

    Event::dispatch(new TestEvent("Hello world!"));
})->throws(CurrentTenantCouldNotBeDeterminedInTenantAwareJob::class);

it('will not fail when no tenant is present and listeners are tenant aware by default', function () {
    config()->set('multitenancy.queues_are_tenant_aware_by_default', true);

    Event::listen(TestEvent::class, ListenerNotTenantAware::class);
    Event::dispatch(new TestEvent("Hello world!"));

    $this->expectExceptionMessage("Method Illuminate\Events\Dispatcher::assertDispatchedTimes does not exist.");

    Event::assertDispatchedTimes(TestEvent::class);
});

it('will inject the current tenant id', function () {
    config()->set('multitenancy.queues_are_tenant_aware_by_default', true);

    $this->tenant->makeCurrent();

    Event::listen(TestEvent::class, ListenerNotTenantAware::class);

    expect(
        Event::dispatch(new TestEvent("Hello world!"))
    )->toEqual([0 => null]);
});
