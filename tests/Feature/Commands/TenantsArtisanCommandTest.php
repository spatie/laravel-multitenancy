<?php

use Illuminate\Support\Facades\Schema;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tasks\SwitchTenantDatabaseTask;

beforeEach(function () {
    config()->set('multitenancy.switch_tenant_tasks', [SwitchTenantDatabaseTask::class]);

    $this->tenant = Tenant::factory()->create(['database' => 'laravel_mt_tenant_1']);
    $this->tenant->execute(fn () => Schema::connection('tenant')->dropIfExists('migrations'));

    $this->anotherTenant = Tenant::factory()->create(['database' => 'laravel_mt_tenant_2']);
    $this->anotherTenant->execute(fn () => Schema::connection('tenant')->dropIfExists('migrations'));
});

it('can migrate all tenant databases', function () {
    $this
        ->artisan('tenants:artisan "migrate --database=tenant"')
        ->assertExitCode(0);

    assertTenantDatabaseHasTable($this->tenant, 'migrations');
    assertTenantDatabaseHasTable($this->anotherTenant, 'migrations');
});

it('can migrate a specific tenant', function () {
    $this->artisan('tenants:artisan "migrate --database=tenant" --tenant="' . $this->anotherTenant->id . '"')->assertExitCode(0);

    assertTenantDatabaseDoesNotHaveTable($this->tenant, 'migrations');
    assertTenantDatabaseHasTable($this->anotherTenant, 'migrations');
});

test("it can't migrate a specific tenant id when search by domain", function () {
    config(['multitenancy.tenant_artisan_search_fields' => 'domain']);

    $this->artisan('tenants:artisan', [
        'artisanCommand' => 'migrate --database=tenant',
        '--tenant' => $this->anotherTenant->id,
    ])
        ->expectsOutput("No tenant(s) found.")
        ->assertExitCode(-1);
});

it('can migrate a specific tenant by domain', function () {
    config(['multitenancy.tenant_artisan_search_fields' => 'domain']);

    $this->artisan('tenants:artisan', [
        'artisanCommand' => 'migrate --database=tenant',
        '--tenant' => $this->anotherTenant->domain,
    ])->assertExitCode(0);

    assertTenantDatabaseDoesNotHaveTable($this->tenant, 'migrations');
    assertTenantDatabaseHasTable($this->anotherTenant, 'migrations');
});
