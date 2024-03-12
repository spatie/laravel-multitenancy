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

it('will separate the cache prefix for each tenant', function () {
    $originalPrefix = config('cache.prefix') ;

    expect(app('cache')->getPrefix())->toStartWith($originalPrefix);
    expect(app('cache.store')->getPrefix())->toStartWith($originalPrefix);

    /** @var \Spatie\Multitenancy\Models\Tenant $tenantOne */
    $tenantOne = Tenant::factory()->create();
    $tenantOne->makeCurrent();
    $tenantOnePrefix = 'tenant_id_' . $tenantOne->id . ':';

    expect($tenantOnePrefix)
        ->toEqual(app('cache')->getPrefix())
        ->toEqual(app('cache.store')->getPrefix());

    /** @var \Spatie\Multitenancy\Models\Tenant $tenantOne */
    $tenantTwo = Tenant::factory()->create();
    $tenantTwo->makeCurrent();
    $tenantTwoPrefix = 'tenant_id_' . $tenantTwo->id . ':';

    expect($tenantTwoPrefix)
        ->toEqual(app('cache')->getPrefix())
        ->toEqual(app('cache.store')->getPrefix());
});

it('will separate the cache for each tenant', function () {
    cache()->put('key', 'cache-landlord');

    /** @var \Spatie\Multitenancy\Models\Tenant $tenantOne */
    $tenantOne = Tenant::factory()->create();
    $tenantOne->makeCurrent();
    $tenantOneVal = 'tenant-' . $tenantOne->domain;

    expect(cache())->has('key')->toBeFalse();

    cache()->put('key', $tenantOneVal);

    /** @var \Spatie\Multitenancy\Models\Tenant $tenantTwo */
    $tenantTwo = Tenant::factory()->create();
    $tenantTwo->makeCurrent();
    $tenantTwoVal = 'tenant-' . $tenantTwo->domain;
    expect(cache())->has('key')->toBeFalse();
    cache()->put('key', $tenantTwoVal);

    $tenantOne->makeCurrent();
    expect($tenantOneVal)
        ->toEqual(app('cache')->get('key'))
        ->toEqual(app('cache.store')->get('key'));

    $tenantTwo->makeCurrent();
    expect($tenantTwoVal)
        ->toEqual(app('cache')->get('key'))
        ->toEqual(app('cache.store')->get('key'));

    Tenant::forgetCurrent();
    expect(cache())->get('key')->toEqual('cache-landlord');
});
