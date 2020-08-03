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

        $this->tenant = factory(Tenant::class)->create(['database' => 'laravel_mt_tenant_1']);

        config()->set('multitenancy.switch_tenant_tasks', [SwitchTenantDatabaseTask::class, SwitchTenantJobsDatabaseTask::class]);
    }

    /** @test */
    public function when_making_a_tenant_current_the_jobs_will_stored_in_his_database(): void
    {
        $this->tenant->makeCurrent();
        $this->migrateTenant();

        $job = new TestFailedJob();
        app(Dispatcher::class)->dispatch($job);

        $hasFailedJobs = DB::table('failed_jobs')->exists();
        $this->assertFalse($hasFailedJobs);

        $this->artisan('tenants:artisan "queue:work --once" --tenant=' . $this->tenant->id);

        $hasFailedJobs = DB::table('failed_jobs')->exists();

        $this->assertTrue($hasFailedJobs);
    }

    public function migrateTenant(): void
    {
        $tenantMigrationsPath = realpath(__DIR__ . '/../../database/migrations');
        $tenantMigrationsPath = str_replace('\\', '/', $tenantMigrationsPath);

        $this->artisan("migrate --database=tenant --path={$tenantMigrationsPath} --realpath")
            ->assertExitCode(0);
    }
}
