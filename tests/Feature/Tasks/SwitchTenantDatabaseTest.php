<?php

namespace Spatie\Multitenancy\Tests\Feature\Tasks;

use Illuminate\Support\Facades\DB;
use Spatie\Multitenancy\Exceptions\InvalidConfiguration;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tasks\SwitchTenantDatabaseTask;
use Spatie\Multitenancy\Tests\TestCase;

class SwitchTenantDatabaseTest extends TestCase
{
    private Tenant $tenant;

    private Tenant $anotherTenant;

    public function setUp(): void
    {
        parent::setUp();

        $this->tenant = factory(Tenant::class)->create(['database' => 'laravel_mt_tenant_1']);

        $this->anotherTenant = factory(Tenant::class)->create(['database' => 'laravel_mt_tenant_2']);

        config()->set('multitenancy.switch_tenant_tasks', [SwitchTenantDatabaseTask::class]);
    }

    /** @test */
    public function switch_fails_if_tenant_database_connection_name_equals_to_landlord_connection_name()
    {
        config()->set('multitenancy.tenant_database_connection_name', null);

        $this->expectException(InvalidConfiguration::class);

        $this->tenant->makeCurrent();
    }

    /** @test */
    public function when_making_a_tenant_current_it_will_perform_the_tasks()
    {
        $this->assertNull(DB::connection('tenant')->getDatabaseName());

        $this->tenant->makeCurrent();

        $this->assertEquals('laravel_mt_tenant_1', DB::connection('tenant')->getDatabaseName());

        $this->anotherTenant->makeCurrent();

        $this->assertEquals('laravel_mt_tenant_2', DB::connection('tenant')->getDatabaseName());

        Tenant::forgetCurrent();

        $this->assertNull(DB::connection('tenant')->getDatabaseName());
    }
}
