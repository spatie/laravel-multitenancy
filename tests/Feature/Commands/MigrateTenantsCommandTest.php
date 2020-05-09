<?php

namespace Spatie\Multitenancy\Tests\Feature\Commands;

use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tests\TestCase;

class MigrateTenantsCommandTest extends TestCase
{
    private Tenant $tenant;

    private Tenant $anotherTenant;

    public function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'tenant']);

        $this->tenant = factory(Tenant::class)->create(['database' => 'laravel_mt_tenant_1']);

        //$this->anotherTenant = factory(Tenant::class)->create();
    }

    /** @test */
    public function it_can_migrate_all_tenant_databases()
    {
        $this->artisan('tenants:migrate')->assertExitCode(0);
    }
}
