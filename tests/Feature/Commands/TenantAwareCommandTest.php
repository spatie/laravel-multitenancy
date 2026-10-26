<?php

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;
use Spatie\Multitenancy\Events\MadeTenantCurrentEvent;
use Spatie\Multitenancy\Events\MakingTenantCurrentEvent;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tasks\SwitchTenantDatabaseTask;
use Spatie\Multitenancy\Tests\Feature\Commands\TestClasses\ThrowForTenantCommand;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

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

it('stops at the tenant that throws by default', function (bool $failLastTenant) {
    $failingTenant = $failLastTenant ? $this->anotherTenant : $this->tenant;

    expect(fn () => $this->artisan('tenant:throw-for-tenant ' . $failingTenant->id)->run())
        ->toThrow(RuntimeException::class, 'Failed for tenant ' . $failingTenant->id);

    expect(ThrowForTenantCommand::$ranForTenantIds)->toBe($failLastTenant
        ? [$this->tenant->id, $this->anotherTenant->id]
        : [$this->tenant->id]);
    expect(Tenant::current())->toBeNull();
})->with([false, true]);

it('continues with the remaining tenants when a tenant throws and --continue-on-error is passed', function (bool $failLastTenant, bool $hasCurrentTenant) {
    Exceptions::fake();
    $failingTenant = $failLastTenant ? $this->anotherTenant : $this->tenant;
    $originalTenant = $hasCurrentTenant ? $this->anotherTenant->makeCurrent() : null;

    $this->artisan('tenant:throw-for-tenant ' . $failingTenant->id . ' --continue-on-error')
        ->expectsOutput("Tenant (id: {$failingTenant->id}) failed: Failed for tenant {$failingTenant->id}")
        ->assertExitCode(Command::FAILURE);

    expect(ThrowForTenantCommand::$ranForTenantIds)->toBe([$this->tenant->id, $this->anotherTenant->id]);
    expect(Tenant::current()?->id)->toBe($originalTenant?->id);
    expect(config('database.connections.tenant.database'))->toBe($originalTenant?->database);

    Exceptions::assertReported(fn (RuntimeException $exception) => $exception->getMessage() === "Failed for tenant {$failingTenant->id}");
    Exceptions::assertReportedCount(1);
})->with([false, true])->with([false, true]);

it('succeeds with --continue-on-error when no tenant throws', function () {
    $this->artisan('tenant:throw-for-tenant 0 --continue-on-error')
        ->assertExitCode(Command::SUCCESS);

    expect(ThrowForTenantCommand::$ranForTenantIds)->toBe([$this->tenant->id, $this->anotherTenant->id]);
});

it('keeps exception failures nonzero at the OS boundary', function (string $command, int $tenantCount) {
    Exceptions::fake();

    Tenant::factory()->count($tenantCount - 2)
        ->sequence(fn (Sequence $sequence) => ['database' => "exception_tenant_{$sequence->index}"])
        ->create();
    $output = new BufferedOutput();

    $exitCode = Artisan::call($command . ' --continue-on-error', [], $output);

    expect(ThrowForTenantCommand::$ranForTenantIds)->toBe(range(1, $tenantCount));
    expect(substr_count($output->fetch(), ') failed: Failed for tenant '))->toBe($tenantCount);
    Exceptions::assertReportedCount($tenantCount);

    $process = new Process([(new PhpExecutableFinder())->find(), '-r', 'exit((int) $argv[1]);', (string) $exitCode]);
    $process->run();

    expect($process->getExitCode())->toBe(Command::FAILURE);
})->with(['tenant:throw-for-tenant', 'tenants:artisan "throw-for-tenant"'])->with([255, 256, 257]);

it('does not cancel an exception with a negative custom command exit code', function (bool $failLastTenant) {
    Exceptions::fake();
    $failingTenant = $failLastTenant ? $this->anotherTenant : $this->tenant;

    $this->artisan("tenant:throw-for-tenant {$failingTenant->id} --exit-code=-1 --continue-on-error")
        ->assertExitCode(Command::FAILURE);

    expect(ThrowForTenantCommand::$ranForTenantIds)->toBe([$this->tenant->id, $this->anotherTenant->id]);
    Exceptions::assertReportedCount(1);
})->with([false, true]);

it('preserves custom exit code aggregation unless continuation is enabled', function (bool $continueOnError, int $expectedExitCode) {
    Exceptions::fake();

    $exitCode = Artisan::call('tenant:throw-for-tenant', [
        'tenantId' => 0,
        '--exit-code' => 7,
        '--continue-on-error' => $continueOnError,
    ]);

    expect($exitCode)->toBe($expectedExitCode);
    expect(ThrowForTenantCommand::$ranForTenantIds)->toBe([$this->tenant->id, $this->anotherTenant->id]);
    Exceptions::assertNothingReported();
})->with([[false, 14], [true, 1]]);

it('only processes and reports selected tenants with continue-on-error', function (string $command, bool $selectFailingTenant) {
    Exceptions::fake();
    $selectedTenant = $selectFailingTenant ? $this->tenant : $this->anotherTenant;
    $output = new BufferedOutput();

    $exitCode = Artisan::call(sprintf($command, $this->tenant->id) . " --continue-on-error --tenant={$selectedTenant->id}", [], $output);

    expect($exitCode)->toBe($selectFailingTenant ? Command::FAILURE : Command::SUCCESS);
    expect(ThrowForTenantCommand::$ranForTenantIds)->toBe([$selectedTenant->id]);
    expect(Tenant::current())->toBeNull();
    Exceptions::assertReportedCount($selectFailingTenant ? 1 : 0);
})->with(['tenant:throw-for-tenant %d', 'tenants:artisan "throw-for-tenant %d"'])->with([false, true]);

it('resets exception status and the continuation option for repeated invocations', function (string $command) {
    Exceptions::fake();

    expect(Artisan::call(sprintf($command, $this->tenant->id) . ' --continue-on-error'))->toBe(Command::FAILURE);
    expect(Artisan::call(sprintf($command, 0) . ' --continue-on-error'))->toBe(Command::SUCCESS);
    expect(fn () => Artisan::call(sprintf($command, $this->anotherTenant->id)))
        ->toThrow(RuntimeException::class, 'Failed for tenant ' . $this->anotherTenant->id);

    expect(ThrowForTenantCommand::$ranForTenantIds)->toBe([
        $this->tenant->id, $this->anotherTenant->id,
        $this->tenant->id, $this->anotherTenant->id,
        $this->tenant->id, $this->anotherTenant->id,
    ]);
    expect(Tenant::current())->toBeNull();
    Exceptions::assertReportedCount(1);
})->with(['tenant:throw-for-tenant %d', 'tenants:artisan "throw-for-tenant %d"']);

it('restores tenant context after a tenant activation exception', function (string $command, bool $failLastTenant, bool $hasCurrentTenant) {
    Exceptions::fake();
    $failingTenant = $failLastTenant ? $this->anotherTenant : $this->tenant;
    $successfulTenant = $failLastTenant ? $this->tenant : $this->anotherTenant;
    $originalTenant = $hasCurrentTenant
        ? Tenant::factory()->create(['database' => 'original_tenant'])->makeCurrent()
        : null;

    Event::listen(MadeTenantCurrentEvent::class, function (MadeTenantCurrentEvent $event) use ($failingTenant) {
        if ($event->tenant->getKey() === $failingTenant->id) {
            throw new RuntimeException('Tenant activation failed');
        }
    });

    $this->artisan("{$command} --continue-on-error --tenant={$this->tenant->id} --tenant={$this->anotherTenant->id}")
        ->expectsOutput("Tenant (id: {$failingTenant->id}) failed: Tenant activation failed")
        ->assertExitCode(Command::FAILURE);

    expect(ThrowForTenantCommand::$ranForTenantIds)->toBe([$successfulTenant->id]);
    expect(Tenant::current()?->id)->toBe($originalTenant?->id);
    expect(Context::get(config('multitenancy.current_tenant_context_key')))->toBe($originalTenant?->id);
    expect(config('database.connections.tenant.database'))->toBe($originalTenant?->database);
    Exceptions::assertReportedCount(1);
})->with(['tenant:throw-for-tenant 0', 'tenants:artisan "throw-for-tenant 0"'])
    ->with([false, true])
    ->with([false, true]);

it('stops if the original tenant context cannot be restored', function (string $command) {
    Exceptions::fake();
    $originalTenant = Tenant::factory()->create(['database' => 'original_tenant'])->makeCurrent();

    Event::listen(MakingTenantCurrentEvent::class, function (MakingTenantCurrentEvent $event) use ($originalTenant) {
        if ($event->tenant->getKey() === $originalTenant->id) {
            throw new RuntimeException('Original tenant unavailable');
        }
    });

    expect(fn () => Artisan::call("{$command} --continue-on-error --tenant={$this->tenant->id} --tenant={$this->anotherTenant->id}"))
        ->toThrow(RuntimeException::class, 'Original tenant unavailable');

    expect(ThrowForTenantCommand::$ranForTenantIds)->toBe([$this->tenant->id]);
    Exceptions::assertReportedCount(1);
})->with(['tenant:throw-for-tenant 0', 'tenants:artisan "throw-for-tenant 0"']);
