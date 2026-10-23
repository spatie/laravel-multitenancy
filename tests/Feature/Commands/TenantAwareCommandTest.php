<?php

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Exceptions;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tasks\SwitchTenantDatabaseTask;
use Spatie\Multitenancy\Tests\Feature\Commands\TestClasses\ThrowForTenantCommand;

beforeEach(function () {
    config()->set('multitenancy.switch_tenant_tasks', [SwitchTenantDatabaseTask::class]);

    $this->tenant = Tenant::factory()->create(['database' => 'laravel_mt_tenant_1']);

    $this->anotherTenant = Tenant::factory()->create(['database' => 'laravel_mt_tenant_2']);

    ThrowForTenantCommand::$ranForTenantIds = [];
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

it('stops at the first tenant that throws by default', function () {
    expect(fn () => $this->artisan('tenant:throw-for-tenant ' . $this->tenant->id)->run())
        ->toThrow(RuntimeException::class, 'Failed for tenant ' . $this->tenant->id);

    expect(ThrowForTenantCommand::$ranForTenantIds)->toBe([$this->tenant->id]);
});

it('continues with the remaining tenants when a tenant throws and --continue-on-error is passed', function () {
    Exceptions::fake();

    $this->artisan('tenant:throw-for-tenant ' . $this->tenant->id . ' --continue-on-error')
        ->expectsOutput("Tenant (id: {$this->tenant->id}) failed: Failed for tenant {$this->tenant->id}")
        ->assertExitCode(Command::FAILURE);

    expect(ThrowForTenantCommand::$ranForTenantIds)->toBe([$this->tenant->id, $this->anotherTenant->id]);
    expect(Tenant::current())->toBeNull();

    Exceptions::assertReported(RuntimeException::class);
});

it('succeeds with --continue-on-error when no tenant throws', function () {
    $this->artisan('tenant:throw-for-tenant 0 --continue-on-error')
        ->assertExitCode(Command::SUCCESS);

    expect(ThrowForTenantCommand::$ranForTenantIds)->toBe([$this->tenant->id, $this->anotherTenant->id]);
});
