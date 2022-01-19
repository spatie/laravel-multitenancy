<?php

namespace Spatie\Multitenancy\Tests\Feature\TenantAwareJobs;

use Illuminate\Support\Facades\Mail;
use Spatie\Multitenancy\Exceptions\CurrentTenantCouldNotBeDeterminedInTenantAwareJob;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses\MailableTenantAware;
use Spatie\Multitenancy\Tests\TestCase;

class QueuedMailableTest extends TestCase
{
    protected Tenant $tenant;

    public function setUp(): void
    {
        parent::setUp();

        config()->set('multitenancy.queues_are_tenant_aware_by_default', true);
        config()->set('queue.default', 'sync');
        config()->set('mail.default', 'log');

        $this->tenant = Tenant::factory()->create();
    }

    /** @test */
    public function it_will_fail_when_no_tenant_is_present_and_mailables_are_tenant_aware_by_default()
    {
        config()->set('multitenancy.queues_are_tenant_aware_by_default', true);

        $this->expectException(CurrentTenantCouldNotBeDeterminedInTenantAwareJob::class);

        Mail::to('test@spatie.be')->queue(new MailableTenantAware());
    }

    /** @test */
    public function it_will_inject_the_current_tenant_id()
    {
        config()->set('multitenancy.queues_are_tenant_aware_by_default', true);

        $this->tenant->makeCurrent();

        $this->assertEquals(Mail::to('test@spatie.be')->queue(new MailableTenantAware()), 0);
    }
}
