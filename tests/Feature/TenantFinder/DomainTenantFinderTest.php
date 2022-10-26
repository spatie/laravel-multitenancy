<?php

use Illuminate\Http\Request;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\TenantFinder\DomainTenantFinder;

beforeEach(function () {
    $this->tenantFinder = new DomainTenantFinder();
});

test('it can find a tenant for the current domain', function () {
    $tenant = Tenant::factory()->create(['domain' => 'my-domain.com']);

    $request = Request::create('https://my-domain.com');

    expect($tenant->id)->toEqual($this->tenantFinder->findForRequest($request)->id);
});

test('it will return null if there are no tenants', function () {
    $request = Request::create('https://my-domain.com');

    expect($this->tenantFinder->findForRequest($request))->toBeNull();
});

test('it will return null if no tenant can be found the current domain', function () {
    Tenant::factory()->create(['domain' => 'my-domain.com']);

    $request = Request::create('https://another-domain.com');

    expect($this->tenantFinder->findForRequest($request))->toBeNull();
});
