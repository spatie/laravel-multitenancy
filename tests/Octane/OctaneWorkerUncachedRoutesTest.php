<?php

use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tests\Octane\TestClasses\UsesRegisterTenantRoutesTask;

uses(UsesRegisterTenantRoutesTask::class);

beforeEach(function () {
    Tenant::factory()->create(['domain' => 'a.test']);

    $this->bootOctaneWorker();
});

it('will keep the routes a task registered for a tenant out of the next landlord request on the same worker', function () {
    expect($this->handleOctaneRequest('http://a.test/tenant-only')->getContent())->toBe('tenant');
    expect($this->handleOctaneRequest('http://landlord.test/tenant-only')->getStatusCode())->toBe(404);

    expect($this->handleOctaneRequest('http://a.test/tenant-only')->getContent())->toBe('tenant');
    expect($this->handleOctaneRequest('http://landlord.test/tenant-only')->getStatusCode())->toBe(404);
});
