<?php

declare(strict_types=1);

use Spatie\Multitenancy\Models\Tenant;

it('returns null when tenant model is not configured', function () {
    config()->set('multitenancy.tenant_model', null);

    expect(tenant())->toBeNull();
});

it('returns null when there is no current tenant', function () {
    Tenant::forgetCurrent();

    expect(tenant())->toBeNull();
});

it('returns current tenant when one is current', function () {
    $mockTenant = Mockery::mock(Tenant::class);
    $mockTenant->shouldReceive('getKey')->andReturn(1);

    app()->instance('currentTenant', $mockTenant);

    expect(tenant())->toBe($mockTenant);

    app()->forgetInstance('currentTenant');
});
