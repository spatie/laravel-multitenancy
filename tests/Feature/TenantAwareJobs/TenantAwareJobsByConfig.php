<?php

namespace Spatie\Multitenancy\Tests\Feature\TenantAwareJobs;

use Illuminate\Contracts\Bus\Dispatcher;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses\TestJob;
use Spatie\Multitenancy\Tests\TestCase;
use Spatie\Valuestore\Valuestore;

class TenantAwareJobsByConfig extends TestCase
{
    protected Tenant $tenant;

    protected Valuestore $valuestore;

    public function setUp(): void
    {
        parent::setUp();

        config()->set('multitenancy.queues_are_tenant_aware_by_default', false);
        config()->set('queue.default', 'sync');
        config()->set('mail.default', 'log');

        $this->tenant = Tenant::factory()->create();
        $this->valuestore = Valuestore::make($this->tempFile('tenantAware.json'))->flush();
    }

    /** @test */
    public function it_success_with_jobs_in_tenant_aware_jobs_list(): void
    {
        config()->set('multitenancy.tenant_aware_jobs', [ TestJob::class ]);

        $this->tenant->makeCurrent();

        app(Dispatcher::class)->dispatch(new TestJob($this->valuestore));

        $this->assertTrue($this->valuestore->has('tenantIdInPayload'));
        $this->assertNotNull($this->valuestore->get('tenantIdInPayload'));
    }

    /** @test */
    public function it_fails_with_jobs_in_not_tenant_aware_jobs_list(): void
    {
        config()->set('multitenancy.not_tenant_aware_jobs', [ TestJob::class ]);

        $this->tenant->makeCurrent();

        app(Dispatcher::class)->dispatch(new TestJob($this->valuestore));

        $this->assertEquals($this->valuestore->get('tenantId'), $this->tenant->id);
        $this->assertTrue($this->valuestore->has('tenantIdInPayload'));
        $this->assertNull($this->valuestore->get('tenantIdInPayload'));
    }
}
