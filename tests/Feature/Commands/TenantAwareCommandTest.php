<?php

use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tasks\SwitchTenantDatabaseTask;

beforeEach(function () {
    config(['database.default' => 'tenant']);
    config()->set('multitenancy.switch_tenant_tasks', [SwitchTenantDatabaseTask::class]);

    $this->tenant = Tenant::factory()->create(['database' => 'laravel_mt_tenant_1']);
    $this->tenant->makeCurrent();

    $this->anotherTenant = Tenant::factory()->create(['database' => 'laravel_mt_tenant_2']);
    $this->anotherTenant->makeCurrent();

    Tenant::forgetCurrent();
});

it('fails with a non-existing tenant')
    ->artisan('tenant:noop --tenant=1000')
    ->assertExitCode(-1)
    ->expectsOutput('No tenant(s) found.');

it('works with no tenant parameters', function () {
    $this
        ->artisan('tenant:noop')
        ->assertExitCode(0)
        ->expectsOutput('Tenant ID is ' . $this->tenant->id)
        ->expectsOutput('Tenant ID is ' . $this->anotherTenant->id);
});
