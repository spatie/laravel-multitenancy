<?php

use Illuminate\Auth\Events\Authenticated;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Spatie\Multitenancy\Exceptions\CurrentTenantCouldNotBeDeterminedInTenantAwareJob;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses\BroadcastNotTenantAware;
use Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses\BroadcastTenantAware;
use Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses\ListenerNotTenantAware;
use Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses\ListenerTenantAware;
use Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses\MailableNotTenantAware;
use Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses\MailableTenantAware;
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

    Broadcast::event(new BroadcastTenantAware("Hello world!"));
})->throws(CurrentTenantCouldNotBeDeterminedInTenantAwareJob::class);

it('will not fail when no tenant is present and listeners are tenant aware by default', function () {
    config()->set('multitenancy.queues_are_tenant_aware_by_default', true);

    Event::listen(TestEvent::class, ListenerNotTenantAware::class);
    Broadcast::event(new BroadcastNotTenantAware("Hello world!"));

    $this->expectExceptionMessage("Method Illuminate\Events\Dispatcher::assertDispatchedTimes does not exist.");

    Event::assertDispatchedTimes(TestEvent::class);
});

it('will inject the current tenant id', function () {
    config()->set('multitenancy.queues_are_tenant_aware_by_default', true);

    $this->tenant->makeCurrent();

    Event::listen(TestEvent::class, ListenerNotTenantAware::class);

    expect(
        Broadcast::event(new BroadcastTenantAware("Hello world!"))
    )->toBeInstanceOf(\Illuminate\Broadcasting\PendingBroadcast::class);
});
