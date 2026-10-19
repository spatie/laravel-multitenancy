<?php

use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tests\Octane\TestClasses\BootsMultitenancyBeforeOctane;

uses(BootsMultitenancyBeforeOctane::class);

beforeEach(function () {
    writeRoutesCache(app()->getCachedRoutesPath(), [
        'current' => fn () => (string) Tenant::current()?->id,
    ]);

    $this->bootOctaneWorker();
});

it('will determine the tenant from the request when the package boots before Octane', function () {
    $tenant = Tenant::factory()->create(['domain' => 'a.test', 'database' => 'laravel_mt_tenant_1']);

    expect($this->handleOctaneRequest('http://a.test/current')->getContent())->toBe((string) $tenant->id);
});
