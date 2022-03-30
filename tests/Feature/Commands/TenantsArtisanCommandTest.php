<?php

namespace Spatie\Multitenancy\Tests\Feature\Commands;

use Illuminate\Support\Facades\Schema;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tasks\SwitchTenantDatabaseTask;
use Spatie\Multitenancy\Tests\TestCase;

class TenantsArtisanCommandTest extends TestCase
{
    protected Tenant $tenant;

    protected Tenant $anotherTenant;

    public function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'tenant']);

        config()->set('multitenancy.switch_tenant_tasks', [SwitchTenantDatabaseTask::class]);

        $this->tenant = Tenant::factory()->create(['database' => 'laravel_mt_tenant_1']);
        $this->tenant->makeCurrent();
        Schema::connection('tenant')->dropIfExists('migrations');

        $this->anotherTenant = Tenant::factory()->create(['database' => 'laravel_mt_tenant_2']);
        $this->anotherTenant->makeCurrent();
        Schema::connection('tenant')->dropIfExists('migrations');

        Tenant::forgetCurrent();
    }

    /** @test */
    public function it_can_migrate_all_tenant_databases()
    {
        $this
            ->artisan('tenants:artisan migrate')
            ->assertExitCode(0);

        $this
            ->assertTenantDatabaseHasTable($this->tenant, 'migrations')
            ->assertTenantDatabaseHasTable($this->anotherTenant, 'migrations');
    }

    /** @test */
    public function it_can_migrate_a_specific_tenant()
    {
        $this->artisan('tenants:artisan migrate --tenant=' . $this->anotherTenant->id . '"')->assertExitCode(0);

        $this
            ->assertTenantDatabaseDoesNotHaveTable($this->tenant, 'migrations')
            ->assertTenantDatabaseHasTable($this->anotherTenant, 'migrations');
    }

    /** @test */
    public function it_cant_migrate_a_specific_tenant_id_when_search_by_domain()
    {
        config([ 'multitenancy.tenant_artisan_search_fields' => 'domain' ]);

        $this->artisan('tenants:artisan', [
                'artisanCommand' => 'migrate',
                '--tenant' => $this->anotherTenant->id
            ])
            ->expectsOutput("No tenant(s) found.")
            ->assertExitCode(-1);
    }

    /** @test */
    public function it_can_migrate_a_specific_tenant_by_domain()
    {
        config([ 'multitenancy.tenant_artisan_search_fields' => 'domain' ]);

        $this->artisan('tenants:artisan', [
            'artisanCommand' => 'migrate',
            '--tenant' => $this->anotherTenant->domain,
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
