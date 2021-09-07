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

class TenantAwareJobThatCannotFindTenantTest extends TestCase
{
    protected Tenant $tenant;

    protected Valuestore $valuestore;

    public function setUp(): void
    {
        parent::setUp();

        config()->set('multitenancy.queues_are_tenant_aware_by_default', true);
        config()->set('queue.default', 'sync');

        $this->tenant = factory(Tenant::class)->create();

        $this->valuestore = Valuestore::make($this->tempFile('tenantAware.json'))->flush();
    }

    /** @test */
    public function it_will_fail_a_job_when_no_tenant_is_present_and_queues_are_tenant_aware_by_default()
    {
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
    }

    /** @test */
    public function it_will_fail_a_job_when_no_tenant_is_present_and_job_implements_the_TenantAware_interface()
    {
        config()->set('multitenancy.queues_are_tenant_aware_by_default', false);

        $job = new TenantAwareTestJob($this->valuestore);

        try {
            app(Dispatcher::class)->dispatch($job);
        } catch (CurrentTenantCouldNotBeDeterminedInTenantAwareJob $exception) {
            $this->assertFalse($this->valuestore->has('tenantId'));

            return;
        }

        $this->fail();
    }

    /** @test */
    public function it_will_not_fail_a_job_when_no_tenant_is_present_and_queues_are_not_tenant_aware_by_default()
    {
        config()->set('multitenancy.queues_are_tenant_aware_by_default', false);

        $job = new TestJob($this->valuestore);

        app(Dispatcher::class)->dispatch($job);

        $this->assertTrue($this->valuestore->has('tenantId'));
        $this->assertNull($this->valuestore->get('tenantId'));
    }

    /** @test */
    public function it_will_not_fail_a_job_when_no_tenant_is_present_and_job_implements_the_NotTenantAware_interface()
    {
        config()->set('multitenancy.queues_are_tenant_aware_by_default', true);

        $job = new NotTenantAwareTestJob($this->valuestore);

        app(Dispatcher::class)->dispatch($job);

        $this->assertTrue($this->valuestore->has('tenantId'));
        $this->assertNull($this->valuestore->get('tenantId'));
    }

    /** @test */
    public function it_will_not_touch_the_tenant_if_the_job_is_not_tenant_aware()
    {
        $this->tenant->makeCurrent();
        $job = new NotTenantAwareTestJob($this->valuestore);

        // Simulate a tenant being set from a previous queue job
        $this->assertTrue(Tenant::checkCurrent());

        app(Dispatcher::class)->dispatch($job);

        // Assert that the active tenant was not modified
        $this->assertSame($this->tenant->id, Tenant::current()->id);
    }
}
