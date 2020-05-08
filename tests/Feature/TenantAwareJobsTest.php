<?php

namespace Spatie\Multitenancy\Tests\Feature;

use Illuminate\Contracts\Bus\Dispatcher;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tests\Feature\TestClasses\TestJob;
use Spatie\Multitenancy\Tests\TestCase;
use Spatie\Valuestore\Valuestore;

class TenantAwareJobsTest extends TestCase
{
    private Tenant $tenant;

    private Valuestore $valuestore;

    public function setUp(): void
    {
        parent::setUp();

        config()->set('multitenancy.queue_is_tenant_aware_by_default', true);

        config()->set('queue.default', 'database');

        config()->set('queue.connections.database', [
            'driver' => 'database',
            'table' => 'jobs',
            'queue' => 'default',
            'retry_after' => 90,
            'connection' => 'landlord',
        ]);

        $this->tenant = factory(Tenant::class)->create();

        $this->valuestore = Valuestore::make($this->tempFile('tenantAware.json'))->flush();
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
    public function it_will_not_break_when_no_tenant_is_set()
    {
        $job = new TestJob($this->valuestore);
        app(Dispatcher::class)->dispatch($job);

        $this->tenant->makeCurrent();

        $this->artisan('queue:work --once')->assertExitCode(0);

        $currentTenantIdInJob = $this->valuestore->get('tenantId');
        $this->assertNull($this->tenant->id, $currentTenantIdInJob);
    }


    /** @test */
    public function it_will_inject_the_right_tenant_even_when_the_current_tenant_switches()
    {
        /** @var \Spatie\Multitenancy\Models\Tenant $anotherTenant */
        $anotherTenant = factory(Tenant::class)->create();

        $this->tenant->makeCurrent();

        $job = new TestJob($this->valuestore);
        app(Dispatcher::class)->dispatch($job);

        $anotherTenant->makeCurrent();
        $this->artisan('queue:work --once');

        $currentTenantIdInJob = $this->valuestore->get('tenantId');
        $this->assertEquals($this->tenant->id, $currentTenantIdInJob);

        $job = new TestJob($this->valuestore);
        app(Dispatcher::class)->dispatch($job);

        Tenant::forgetCurrent();

        $currentTenantIdInJob = $this->valuestore->get('tenantId');
        $this->assertEquals($anotherTenant->id, $currentTenantIdInJob);
    }
}
