<?php

use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tasks\PrefixCacheTask;

beforeEach(function () {
    config()->set('multitenancy.switch_tenant_tasks', [PrefixCacheTask::class]);

    config()->set('cache.default', 'redis');

    app()->forgetInstance('cache');

    app()->forgetInstance('cache.store');

    app('cache')->flush();
});

test('it will separate the cache prefix for each tenant', function () {
    $originalPrefix = config('cache.prefix') . ':';
    $this->assertEquals($originalPrefix, app('cache')->getPrefix());
    $this->assertEquals($originalPrefix, app('cache.store')->getPrefix());

    /** @var \Spatie\Multitenancy\Models\Tenant $tenantOne */
    $tenantOne = Tenant::factory()->create();
    $tenantOne->makeCurrent();
    $tenantOnePrefix = 'tenant_id_' . $tenantOne->id . ':';
    $this->assertEquals($tenantOnePrefix, app('cache')->getPrefix());
    $this->assertEquals($tenantOnePrefix, app('cache.store')->getPrefix());

    /** @var \Spatie\Multitenancy\Models\Tenant $tenantOne */
    $tenantTwo = Tenant::factory()->create();
    $tenantTwo->makeCurrent();
    $tenantTwoPrefix = 'tenant_id_' . $tenantTwo->id . ':';
    $this->assertEquals($tenantTwoPrefix, app('cache')->getPrefix());
    $this->assertEquals($tenantTwoPrefix, app('cache.store')->getPrefix());
});

test('it will separate the cache for each tenant', function () {
    cache()->put('key', 'cache-landlord');

    /** @var \Spatie\Multitenancy\Models\Tenant $tenantOne */
    $tenantOne = Tenant::factory()->create();
    $tenantOne->makeCurrent();
    $tenantOneVal = 'tenant-' . $tenantOne->domain;
    $this->assertFalse(cache()->has('key'));
    cache()->put('key', $tenantOneVal);

    /** @var \Spatie\Multitenancy\Models\Tenant $tenantTwo */
    $tenantTwo = Tenant::factory()->create();
    $tenantTwo->makeCurrent();
    $tenantTwoVal = 'tenant-' . $tenantTwo->domain;
    $this->assertFalse(cache()->has('key'));
    cache()->put('key', $tenantTwoVal);

    $tenantOne->makeCurrent();
    $this->assertEquals($tenantOneVal, app('cache')->get('key'));
    $this->assertEquals($tenantOneVal, app('cache.store')->get('key'));

    $tenantTwo->makeCurrent();
    $this->assertEquals($tenantTwoVal, app('cache')->get('key'));
    $this->assertEquals($tenantTwoVal, app('cache.store')->get('key'));

    Tenant::forgetCurrent();
    $this->assertEquals('cache-landlord', cache()->get('key'));
});
