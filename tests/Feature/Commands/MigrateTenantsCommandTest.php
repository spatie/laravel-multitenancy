<?php

namespace Spatie\Multitenancy\Tests\Feature\Commands;

use Illuminate\Support\Facades\Schema;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tasks\SwitchTenantDatabase;
use Spatie\Multitenancy\Tests\TestCase;

class MigrateTenantsCommandTest extends TestCase
{
    private Tenant $tenant;

    private Tenant $anotherTenant;

    public function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'tenant']);

        config()->set('multitenancy.switch_tenant_tasks', [SwitchTenantDatabase::class]);

        $this->tenant = factory(Tenant::class)->create(['database' => 'laravel_mt_tenant_1']);
        $this->tenant->makeCurrent();
        Schema::connection('tenant')->dropIfExists('migrations');

        $this->anotherTenant = factory(Tenant::class)->create(['database' => 'laravel_mt_tenant_2']);
        $this->anotherTenant->makeCurrent();
        Schema::connection('tenant')->dropIfExists('migrations');

        Tenant::forgetCurrent();
    }

    /** @test */
    public function it_can_migrate_all_tenant_databases()
    {
        $this->artisan('tenants:migrate')->assertExitCode(0);

        $this
            ->assertTenantDatabaseHasTable($this->tenant, 'migrations')
            ->assertTenantDatabaseHasTable($this->anotherTenant, 'migrations');
    }

    /** @test */
    public function it_can_migrate_a_specific_tenant()
    {
        $this->artisan('tenants:migrate', [
            'tenantId' => $this->anotherTenant->id,
        ])->assertExitCode(0);

        $this
            ->assertTenantDatabaseDoesNotHaveTable($this->tenant, 'migrations')
            ->assertTenantDatabaseHasTable($this->anotherTenant, 'migrations');
    }

    protected function assertTenantDatabaseHasTable(Tenant $tenant, string $tableName): self
    {
        $tenantHasDatabaseTable = $this->tenantHasDatabaseTable($tenant, $tableName);

        $this->assertTrue($tenantHasDatabaseTable, "Tenant database does not have table  `{$tableName}`");

        return $this;
    }

    protected function assertTenantDatabaseDoesNotHaveTable(Tenant $tenant, string $tableName): self
    {
        $tenantHasDatabaseTable = $this->tenantHasDatabaseTable($tenant, $tableName);

        $this->assertFalse($tenantHasDatabaseTable, "Tenant database has unexpected table  `{$tableName}`");

        return $this;
    }

    protected function tenantHasDatabaseTable(Tenant $tenant, string $tableName): bool
    {
        $tenant->makeCurrent();

        $tenantHasDatabaseTable = Schema::connection('tenant')->hasTable($tableName);

        Tenant::forgetCurrent();

        return $tenantHasDatabaseTable;
    }
}
