<?php

namespace Spatie\Multitenancy\Tests\Feature\TenantAwareJobs;

use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Queue\Events\JobFailed;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses\NotTenantAwareTestJob;
use Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses\TenantAwareTestJob;
use Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses\TestJob;
use Spatie\Multitenancy\Tests\TestCase;
use Spatie\Valuestore\Valuestore;

class QueueIsTenantAwareByDefaultTest extends TestCase
{
    private Tenant $tenant;

    private Valuestore $valuestore;

    public function setUp(): void
    {
        parent::setUp();

        config()->set('multitenancy.queues_are_tenant_aware_by_default', true);

        $this->tenant = factory(Tenant::class)->create();

        $this->valuestore = Valuestore::make($this->tempFile('tenantAware.json'))->flush();

        $this->doesntExpectEvents(JobFailed::class);
    }

    /** @test */
    public function it_will_inject_the_current_tenant_id()
    {
        $this->tenant->makeCurrent();

        $job = new TestJob($this->valuestore);
        app(Dispatcher::class)->dispatch($job);

        Tenant::forgetCurrent();

        $this->artisan('queue:work --once')->assertExitCode(0);

        $currentTenantIdInJob = $this->valuestore->get('tenantId');
        $this->assertEquals($this->tenant->id, $currentTenantIdInJob);
    }

    /** @test */
    public function it_will_inject_the_right_tenant_even_when_the_current_tenant_switches()
    {
        /** @var \Spatie\Multitenancy\Models\Tenant $anotherTenant */
        $anotherTenant = factory(Tenant::class)->create();

        $this->tenant->makeCurrent();
        $job = new TestJob($this->valuestore);
        app(Dispatcher::class)->dispatch($job);

        $this->artisan('queue:work --once');

        $currentTenantIdInJob = $this->valuestore->get('tenantId');
        $this->assertEquals($this->tenant->id, $currentTenantIdInJob);

        $anotherTenant->makeCurrent();
        $job = new TestJob($this->valuestore);
        app(Dispatcher::class)->dispatch($job);

        $this->artisan('queue:work --once');

        $currentTenantIdInJob = $this->valuestore->get('tenantId');
        $this->assertEquals($anotherTenant->id, $currentTenantIdInJob);
    }

    /** @test */
    public function it_will_not_make_jobs_tenant_aware_if_the_config_setting_is_set_to_false()
    {
        config()->set('multitenancy.queues_are_tenant_aware_by_default', false);

        $this->tenant->makeCurrent();

        $job = new TestJob($this->valuestore);
        app(Dispatcher::class)->dispatch($job);

        $this->artisan('queue:work --once')->assertExitCode(0);

        $currentTenantIdInJob = $this->valuestore->get('tenantIdInPayload');
        $this->assertNull($currentTenantIdInJob);
    }

    /** @test */
    public function it_will_always_make_jobs_tenant_aware_if_they_implement_the_TenantAware_interface()
    {
        config()->set('multitenancy.queues_are_tenant_aware_by_default', false);

        $this->tenant->makeCurrent();

        $job = new TenantAwareTestJob($this->valuestore);
        app(Dispatcher::class)->dispatch($job);

        $this->artisan('queue:work --once')->assertExitCode(0);

        $currentTenantIdInJob = $this->valuestore->get('tenantId');
        $this->assertEquals($this->tenant->id, $currentTenantIdInJob);
    }

    /** @test */
    public function it_will_not_make_a_job_tenant_aware_if_it_implement_NotTenantAware()
    {
        config()->set('multitenancy.queues_are_tenant_aware_by_default', true);

        $this->tenant->makeCurrent();

        $job = new NotTenantAwareTestJob($this->valuestore);
        app(Dispatcher::class)->dispatch($job);

        $this->artisan('queue:work --once')->assertExitCode(0);

        $currentTenantIdInJob = $this->valuestore->get('tenantIdInPayload');
        $this->assertNull($currentTenantIdInJob);
    }
}
