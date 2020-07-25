<?php

namespace Spatie\Multitenancy\Tasks;

use Illuminate\Support\Facades\DB;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tests\Feature\Tasks\TestClasses\TestFailedJob;
use Spatie\Multitenancy\Tests\TestCase;
use Illuminate\Contracts\Bus\Dispatcher;

class SwitchTenantJobsDatabaseTest extends TestCase
{
    private Tenant $tenant;

    public function setUp(): void
    {
        parent::setUp();

        $this->tenant = factory(Tenant::class)->create();
        config()->set('multitenancy.switch_tenant_tasks', [SwitchTenantDatabaseTask::class, SwitchTenantJobsDatabaseTask::class]);
    }

    /** @test */
    public function when_making_a_tenant_current_the_jobs_will_stored_in_his_database()
    {
        $this->tenant->makeCurrent();

        $job = new TestFailedJob();
        app(Dispatcher::class)->dispatch($job);

        $hasJob = DB::table('jobs')->exists();
        $this->assertTrue($hasJob);

        $hasFailedJobs = DB::table('failed_jobs')->exists();
        $this->assertFalse($hasFailedJobs);

        $this->artisan('queue:work --once');

        $hasFailedJobs = DB::table('failed_jobs')->exists();
        $this->assertTrue($hasFailedJobs);
    }
}
