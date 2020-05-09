<?php

namespace Spatie\Multitenancy\Tests\Feature\Tasks;

use Illuminate\Support\Facades\DB;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tasks\SwitchTenantDatabase;
use Spatie\Multitenancy\Tests\TestCase;

class SwitchTenantDatabaseTest extends TestCase
{
    private Tenant $tenant;

    public function setUp(): void
    {
        parent::setUp();

        $this->tenant = factory(Tenant::class)->create(['database' => 'laravel_mt_tenant_1']);

        config()->set('multitenancy.switch_tenant_tasks', [SwitchTenantDatabase::class]);
    }

    /** @test */
    public function when_making_a_tenant_current_it_will_perform_the_tasks()
    {
        $this->assertNull(DB::connection('tenant')->getDatabaseName());

        $this->tenant->makeCurrent();

        $this->assertEquals('laravel_mt_tenant_1', DB::connection('tenant')->getDatabaseName());
    }
}
