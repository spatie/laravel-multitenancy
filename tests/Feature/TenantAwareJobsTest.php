<?php

namespace Spatie\Multitenancy\Tests\Feature;

use Illuminate\Support\Facades\Queue;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tests\Feature\TestClasses\TestJob;
use Spatie\Multitenancy\Tests\TestCase;

class TenantAwareJobsTest extends TestCase
{
    private Tenant $tenant;

    public function setUp(): void
    {
        parent::setUp();

        config()->set('multitenancy.queue_is_tenant_aware_by_default', true);

        $this->tenant = factory(Tenant::class)->create();
    }

    /** @test */
    public function it_will_inject_the_current_tenant_id()
    {
        $this->tenant->makeCurrent();

        $job = new TestJob();

        dispatch($job);

        Queue::assertPushed(TestJob::class, function (TestJob $job) {
            dd('in assert pushed');
        });

        $this->assertTrue(TestJob::$jobHandled);
    }
}
