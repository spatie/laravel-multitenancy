<?php

namespace Spatie\Multitenancy\Tests\Feature\TenantAwareJobs;

use Illuminate\Support\Facades\Notification;
use Spatie\Multitenancy\Exceptions\CurrentTenantCouldNotBeDeterminedInTenantAwareJob;
use Spatie\Multitenancy\Tests\Feature\Models\TenantNotifiable;
use Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses\NotificationTenantAware;
use Spatie\Multitenancy\Tests\TestCase;

class QueuedNotificationsTest extends TestCase
{
    protected TenantNotifiable $tenant;

    public function setUp(): void
    {
        parent::setUp();

        config()->set('multitenancy.queues_are_tenant_aware_by_default', true);
        config()->set('queue.default', 'sync');
        config()->set('mail.default', 'log');

        $this->tenant = TenantNotifiable::factory()->create();
    }

    /** @test */
    public function it_will_fail_when_no_tenant_is_present_and_mailables_are_tenant_aware_by_default()
    {
        config()->set('multitenancy.queues_are_tenant_aware_by_default', true);

        $this->expectException(CurrentTenantCouldNotBeDeterminedInTenantAwareJob::class);

        $this->tenant->notify((new NotificationTenantAware())->delay(now()->addSecond()));

        Notification::assertNothingSent();
    }

    /** @test */
    public function it_will_inject_the_current_tenant_id()
    {
        config()->set('multitenancy.queues_are_tenant_aware_by_default', true);

        $this->tenant->makeCurrent();

        $this->tenant->notify((new NotificationTenantAware())->delay(now()->addSecond()));

        $this->expectExceptionMessage("Call to undefined method Illuminate\Notifications\Channels\MailChannel::assertNothingSent()");

        Notification::assertNothingSent();
    }
}
