<?php

namespace Spatie\Multitenancy\Tests\Feature\Commands;

use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tasks\SwitchTenantDatabaseTask;
use Spatie\Multitenancy\Tests\Feature\Commands\TestClasses\TenantNoopCommand;
use Spatie\Multitenancy\Tests\TestCase;

class TenantAwareCommandTest extends TestCase
{
    private Tenant $tenant;

    private Tenant $anotherTenant;

    public function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'tenant']);

        config()->set('multitenancy.switch_tenant_tasks', [SwitchTenantDatabaseTask::class]);

        $this->tenant = factory(Tenant::class)->create(['database' => 'laravel_mt_tenant_1']);
        $this->tenant->makeCurrent();

        $this->anotherTenant = factory(Tenant::class)->create(['database' => 'laravel_mt_tenant_2']);
        $this->anotherTenant->makeCurrent();

        Tenant::forgetCurrent();
    }

    /** @test */
    public function it_fails_with_a_not_existent_tenant()
    {
        $this
            ->artisan('tenant:noop --tenant=1000')
            ->assertExitCode(-1)
            ->expectsOutput('No tenant(s) found.');
    }

    /** @test */
    public function it_prints_the_right_tenant()
    {
        $this
            ->artisan('tenant:noop --tenant=1')
            ->assertExitCode(0)
            ->expectsOutput('Tenant ID is '. $this->tenant->id);
    }

    /** @test */
    public function it_works_with_no_tenant_parameters()
    {
        $this
            ->artisan('tenant:noop')
            ->assertExitCode(0)
            ->expectsOutput('Tenant ID is '. $this->tenant->id)
            ->expectsOutput('Tenant ID is '. $this->anotherTenant->id);
    }
}
