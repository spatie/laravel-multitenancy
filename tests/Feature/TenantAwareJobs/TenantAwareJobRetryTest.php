<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses\FailingTenantAwareTestJob;
use Spatie\Valuestore\Valuestore;

beforeEach(function () {
    config()->set('multitenancy.queues_are_tenant_aware_by_default', true);

    config()->set('queue.failed', [
        'driver' => 'database-uuids',
        'database' => 'landlord',
        'table' => 'failed_jobs',
    ]);

    Schema::connection('landlord')->dropIfExists('failed_jobs');

    Schema::connection('landlord')->create('failed_jobs', function (Blueprint $table) {
        $table->id();
        $table->string('uuid')->unique();
        $table->text('connection');
        $table->text('queue');
        $table->longText('payload');
        $table->longText('exception');
        $table->timestamp('failed_at')->useCurrent();
    });

    $this->tenant = Tenant::factory()->create();

    $this->valuestore = Valuestore::make(tempFile('tenantAware.json'))->flush();
});

it('can determine the tenant when retrying a failed tenant aware job', function () {
    $this->tenant->makeCurrent();

    dispatch(new FailingTenantAwareTestJob($this->valuestore));

    Tenant::forgetCurrent();

    $this->artisan('queue:work --once');

    expect(DB::connection('landlord')->table('failed_jobs')->count())->toBe(1);

    $this->valuestore->put('shouldFail', false);

    Tenant::forgetCurrent();
    Context::flush();

    $this->artisan('queue:retry all')->assertExitCode(0);

    $this->artisan('queue:work --once')->assertExitCode(0);

    expect($this->valuestore->get('tenantId'))->toEqual($this->tenant->id);
});

it('restores the right tenant when retrying failed jobs of different tenants', function () {
    $otherTenant = Tenant::factory()->create();

    $otherValuestore = Valuestore::make(tempFile('otherTenantAware.json'))->flush();

    $this->tenant->makeCurrent();
    dispatch(new FailingTenantAwareTestJob($this->valuestore));

    $otherTenant->makeCurrent();
    dispatch(new FailingTenantAwareTestJob($otherValuestore));

    Tenant::forgetCurrent();

    $this->artisan('queue:work --once');
    $this->artisan('queue:work --once');

    expect(DB::connection('landlord')->table('failed_jobs')->count())->toBe(2);

    $this->valuestore->put('shouldFail', false);
    $otherValuestore->put('shouldFail', false);

    Tenant::forgetCurrent();
    Context::flush();

    $this->artisan('queue:retry all')->assertExitCode(0);

    $this->artisan('queue:work --once');
    $this->artisan('queue:work --once');

    expect($this->valuestore->get('tenantId'))->toEqual($this->tenant->id)
        ->and($otherValuestore->get('tenantId'))->toEqual($otherTenant->id);
});
