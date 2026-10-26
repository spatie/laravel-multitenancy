<?php

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Spatie\Multitenancy\Commands\TenantsArtisanCommand;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tasks\SwitchTenantDatabaseTask;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

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
        ->assertExitCode(Command::FAILURE);
});

it('succeeds when there are no tenants yet, so a first deploy is not aborted', function () {
    Tenant::query()->delete();

    $this->artisan('tenants:artisan "migrate --database=tenant"')
        ->expectsOutput('No tenants found, skipping.')
        ->assertExitCode(Command::SUCCESS);
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

it('fails when the artisan command fails for a tenant', function (bool $failLastTenant) {
    $failingTenant = $failLastTenant ? $this->anotherTenant : $this->tenant;

    $this->artisan('tenants:artisan "fail-for-tenant ' . $failingTenant->id . '"')
        ->expectsOutput('Ran for tenant ' . $this->tenant->id)
        ->expectsOutput('Ran for tenant ' . $this->anotherTenant->id)
        ->assertExitCode(Command::FAILURE);
})->with([false, true]);

it('succeeds when the artisan command fails only for an unselected tenant', function () {
    $this->artisan('tenants:artisan "fail-for-tenant ' . $this->tenant->id . '" --tenant="' . $this->anotherTenant->id . '"')
        ->doesntExpectOutput('Ran for tenant ' . $this->tenant->id)
        ->expectsOutput('Ran for tenant ' . $this->anotherTenant->id)
        ->assertExitCode(Command::SUCCESS);
});

it('fails when the selected tenant command fails', function () {
    $this->artisan('tenants:artisan', [
        'artisanCommand' => 'fail-for-tenant ' . $this->anotherTenant->id,
        '--tenant' => [$this->anotherTenant->id],
    ])
        ->doesntExpectOutput('Ran for tenant ' . $this->tenant->id)
        ->expectsOutput('Ran for tenant ' . $this->anotherTenant->id)
        ->assertExitCode(Command::FAILURE);
});

it('returns a failing OS exit status when tenant exit codes sum to a multiple of 256', function () {
    $output = new BufferedOutput();

    $exitCode = Artisan::call('tenants:artisan', [
        'artisanCommand' => 'fail-for-tenant --exit-code=128',
    ], $output);

    expect($output->fetch())
        ->toContain('Ran for tenant ' . $this->tenant->id)
        ->toContain('Ran for tenant ' . $this->anotherTenant->id);

    $process = new Process([(new PhpExecutableFinder())->find(), '-r', 'exit((int) $argv[1]);', (string) $exitCode]);
    $process->run();

    expect($process->getExitCode())->toBe(Command::FAILURE);
});

it('does not retain failure across invocations of the same command', function () {
    $this->artisan('tenants:artisan "fail-for-tenant"')
        ->assertExitCode(Command::FAILURE);

    $this->artisan('tenants:artisan "fail-for-tenant --exit-code=0"')
        ->expectsOutput('Ran for tenant ' . $this->tenant->id)
        ->expectsOutput('Ran for tenant ' . $this->anotherTenant->id)
        ->assertExitCode(Command::SUCCESS);
});

it('supports existing subclasses with a void handle override', function () {
    app(Kernel::class)->registerCommand(new class () extends TenantsArtisanCommand {
        protected $signature = 'custom-tenants:artisan {artisanCommand} {--tenant=*}';

        public function handle(): void
        {
            $this->line('Custom tenant command');

            parent::handle();
        }
    });

    $this->artisan('custom-tenants:artisan "fail-for-tenant ' . $this->tenant->id . '"')
        ->expectsOutput('Custom tenant command')
        ->expectsOutput('Ran for tenant ' . $this->tenant->id)
        ->expectsOutput('Custom tenant command')
        ->expectsOutput('Ran for tenant ' . $this->anotherTenant->id)
        ->assertExitCode(Command::FAILURE);
});
