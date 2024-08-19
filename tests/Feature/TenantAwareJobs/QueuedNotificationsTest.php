<?php

use Illuminate\Support\Facades\Notification;
use Spatie\Multitenancy\Exceptions\CurrentTenantCouldNotBeDeterminedInTenantAwareJob;
use Spatie\Multitenancy\Tests\Feature\Models\TenantNotifiable;
use Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses\NotificationNotTenantAware;
use Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses\NotificationTenantAware;

beforeEach(function () {
    config()->set('multitenancy.queues_are_tenant_aware_by_default', true);
    config()->set('queue.default', 'sync');
    config()->set('mail.default', 'log');

    $this->tenant = TenantNotifiable::factory()->create();
});

it('will fail when no tenant is present and mailables are tenant aware by default', function () {
    config()->set('multitenancy.queues_are_tenant_aware_by_default', true);

    $this->tenant->notify((new NotificationTenantAware())->delay(now()->addSecond()));

    Notification::assertNothingSent();
})->throws(CurrentTenantCouldNotBeDeterminedInTenantAwareJob::class);

it('will not fail when no tenant is present and mailables are tenant aware by default', function () {
    config()->set('multitenancy.queues_are_tenant_aware_by_default', true);

    $this->tenant->notify((new NotificationNotTenantAware()));

    $this->expectExceptionMessage("Call to undefined method Illuminate\Notifications\Channels\MailChannel::assertCount()");

    Notification::assertCount(1);
});

it('will inject the current tenant id', function () {
    config()->set('multitenancy.queues_are_tenant_aware_by_default', true);

    $this->tenant->makeCurrent();

    $this->tenant->notify((new NotificationTenantAware())->delay(now()->addSecond()));

    $this->expectExceptionMessage("Call to undefined method Illuminate\Notifications\Channels\MailChannel::assertNothingSent()");

    Notification::assertNothingSent();
});
