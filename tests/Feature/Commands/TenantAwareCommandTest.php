<?php

use Illuminate\Console\Command;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tasks\SwitchTenantDatabaseTask;

beforeEach(function () {
    config()->set('multitenancy.switch_tenant_tasks', [SwitchTenantDatabaseTask::class]);

    $this->tenant = Tenant::factory()->create(['database' => 'laravel_mt_tenant_1']);

    $this->anotherTenant = Tenant::factory()->create(['database' => 'laravel_mt_tenant_2']);
});

it('fails with a non-existing tenant')
    ->artisan('tenant:noop --tenant=1000')
    ->assertExitCode(Command::FAILURE)
    ->expectsOutput('No tenant(s) found.');

it('succeeds when no tenants exist at all', function () {
    Tenant::query()->delete();

    $this
        ->artisan('tenant:noop')
        ->assertExitCode(Command::SUCCESS)
        ->expectsOutput('No tenants found, skipping.');
});

it('works with no tenant parameters', function () {
    $this
        ->artisan('tenant:noop')
        ->assertExitCode(0)
        ->expectsOutput('Tenant ID is ' . $this->tenant->id)
        ->expectsOutput('Tenant ID is ' . $this->anotherTenant->id);
});
