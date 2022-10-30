<?php

use Illuminate\Support\Facades\DB;
use Spatie\Multitenancy\Exceptions\InvalidConfiguration;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tasks\SwitchTenantDatabaseTask;
use Spatie\Multitenancy\Tests\TestClasses\User;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create(['database' => 'laravel_mt_tenant_1']);

    $this->anotherTenant = Tenant::factory()->create(['database' => 'laravel_mt_tenant_2']);

    config()->set('multitenancy.switch_tenant_tasks', [SwitchTenantDatabaseTask::class]);
});

test('switch fails if tenant database connection name equals to landlord connection name', function () {
    config()->set('multitenancy.tenant_database_connection_name', null);

    $this->tenant->makeCurrent();
})->throws(InvalidConfiguration::class);

test('when making a tenant current it will perform the tasks', function () {
    expect(DB::connection('tenant'))->getDatabaseName()->toBeNull();

    $this->tenant->makeCurrent();

    expect('laravel_mt_tenant_1')
        ->toEqual(DB::connection('tenant')->getDatabaseName())
        ->toEqual(app(User::class)->getConnection()->getDatabaseName());

    $this->anotherTenant->makeCurrent();

    expect('laravel_mt_tenant_2')
        ->toEqual(DB::connection('tenant')->getDatabaseName())
        ->toEqual(app(User::class)->getConnection()->getDatabaseName());

    Tenant::forgetCurrent();
    expect(DB::connection('tenant'))->getDatabaseName()->toBeNull();
});
