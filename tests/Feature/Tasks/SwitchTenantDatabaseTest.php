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

    $this->expectException(InvalidConfiguration::class);

    $this->tenant->makeCurrent();
});

test('when making a tenant current it will perform the tasks', function () {
    $this->assertNull(DB::connection('tenant')->getDatabaseName());

    $this->tenant->makeCurrent();

    $this->assertEquals('laravel_mt_tenant_1', DB::connection('tenant')->getDatabaseName());
    $this->assertEquals('laravel_mt_tenant_1', app(User::class)->getConnection()->getDatabaseName());

    $this->anotherTenant->makeCurrent();

    $this->assertEquals('laravel_mt_tenant_2', DB::connection('tenant')->getDatabaseName());
    $this->assertEquals('laravel_mt_tenant_2', app(User::class)->getConnection()->getDatabaseName());

    Tenant::forgetCurrent();

    $this->assertNull(DB::connection('tenant')->getDatabaseName());
});
