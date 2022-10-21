<?php

namespace Spatie\Multitenancy\Tests\Feature;

use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\TenantCollection;
use Spatie\Multitenancy\Tests\TestCase;

beforeEach(function () {
    Tenant::factory()->count(3)->create();

    $this->tenants = Tenant::get();
});

test('it can make each tenant current', function () {
    $this->tenants->eachCurrent(function (Tenant $tenant) {
        $this->assertEquals($tenant->id, Tenant::current()->id);
    });
});

test('after making each tenant current, the original current tenant is made current again', function () {
    $this->assertFalse(Tenant::checkCurrent());

    $this->tenants->eachCurrent(function (Tenant $tenant) {
    });

    $this->assertFalse(Tenant::checkCurrent());

    $this->tenants[1]->makeCurrent();

    $this->tenants->eachCurrent(function (Tenant $tenant) {
    });

    $this->assertTrue($this->tenants[1]->isCurrent());
});

test('it can map while making each tenant current', function () {
    $tenantIds = $this->tenants
        ->mapCurrent(function (Tenant $tenant) {
            $this->assertEquals($tenant->id, Tenant::current()->id);

            return $tenant->id;
        })
        ->toArray();

    $this->assertEquals([1, 2, 3], $tenantIds);
});

test('after mapping each current tenant the original current tenant is made current again', function () {
    $this->assertFalse(Tenant::checkCurrent());

    $this->tenants->mapCurrent(function (Tenant $tenant) {
    });

    $this->assertFalse(Tenant::checkCurrent());

    $this->tenants[1]->makeCurrent();

    $this->tenants->mapCurrent(function (Tenant $tenant) {
    });

    $this->assertTrue($this->tenants[1]->isCurrent());
});

test('it can filter while making each tenant current', function () {
    $tenantIds = $this->tenants
        ->filterCurrent(function (Tenant $tenant) {
            $this->assertEquals($tenant->id, Tenant::current()->id);

            return $tenant->id != 2;
        })
        ->pluck('id')
        ->toArray();

    $this->assertEquals([1, 3], $tenantIds);
});

test('after filtering each current tenant the original current tenant is made current again', function () {
    $this->assertFalse(Tenant::checkCurrent());

    $this->tenants->filterCurrent(function (Tenant $tenant) {
    });

    $this->assertFalse(Tenant::checkCurrent());

    $this->tenants[1]->makeCurrent();

    $this->tenants->filterCurrent(function (Tenant $tenant) {
    });

    $this->assertTrue($this->tenants[1]->isCurrent());
});

test('it can reject while making each tenant current', function () {
    $tenantIds = $this->tenants
        ->rejectCurrent(function (Tenant $tenant) {
            $this->assertEquals($tenant->id, Tenant::current()->id);

            return $tenant->id == 2;
        })
        ->pluck('id')
        ->toArray();

    $this->assertEquals([1, 3], $tenantIds);
});

test('after rejecting each current tenant the original current tenant is made current again', function () {
    $this->assertFalse(Tenant::checkCurrent());

    $this->tenants->rejectCurrent(function (Tenant $tenant) {
    });

    $this->assertFalse(Tenant::checkCurrent());

    $this->tenants[1]->makeCurrent();

    $this->tenants->rejectCurrent(function (Tenant $tenant) {
    });

    $this->assertTrue($this->tenants[1]->isCurrent());
});
