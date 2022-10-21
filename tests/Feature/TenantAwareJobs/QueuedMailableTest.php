<?php

namespace Spatie\Multitenancy\Tests\Feature\TenantAwareJobs;

use Illuminate\Support\Facades\Mail;
use Spatie\Multitenancy\Exceptions\CurrentTenantCouldNotBeDeterminedInTenantAwareJob;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses\MailableTenantAware;

beforeEach(function () {
    config()->set('multitenancy.queues_are_tenant_aware_by_default', true);
    config()->set('queue.default', 'sync');
    config()->set('mail.default', 'log');

    $this->tenant = Tenant::factory()->create();
});

test('it will fail when no tenant is present and mailables are tenant aware by default', function () {
    config()->set('multitenancy.queues_are_tenant_aware_by_default', true);

    $this->expectException(CurrentTenantCouldNotBeDeterminedInTenantAwareJob::class);

    Mail::to('test@spatie.be')->queue(new MailableTenantAware());
});

test('it will inject the current tenant id', function () {
    config()->set('multitenancy.queues_are_tenant_aware_by_default', true);

    $this->tenant->makeCurrent();

    $this->assertEquals(Mail::to('test@spatie.be')->queue(new MailableTenantAware()), 0);
});
