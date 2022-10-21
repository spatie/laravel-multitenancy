<?php

namespace Spatie\Multitenancy\Tests\Feature\Models;

use Illuminate\Support\Facades\Event;
use Spatie\Multitenancy\Events\ForgettingCurrentTenantEvent;
use Spatie\Multitenancy\Events\ForgotCurrentTenantEvent;
use Spatie\Multitenancy\Events\MadeTenantCurrentEvent;
use Spatie\Multitenancy\Events\MakingTenantCurrentEvent;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tests\TestCase;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
});

test('it can get the current tenant', function () {
    $this->assertNull(Tenant::current());

    $this->tenant->makeCurrent();

    $this->assertEquals($this->tenant->id, Tenant::current()->id);
});

test('it will bind the current tenant in the container', function () {
    $containerKey = config('multitenancy.current_tenant_container_key');

    $this->assertFalse(app()->has($containerKey));

    $this->tenant->makeCurrent();

    $this->assertTrue(app()->has($containerKey));

    $this->assertInstanceOf(Tenant::class, app($containerKey));
    $this->assertEquals($this->tenant->id, app($containerKey)->id);
});

test('it can forget the current tenant', function () {
    $containerKey = config('multitenancy.current_tenant_container_key');

    $this->tenant->makeCurrent();
    $this->assertEquals($this->tenant->id, Tenant::current()->id);
    $this->assertTrue(app()->has($containerKey));

    Tenant::forgetCurrent();
    $this->assertNull(Tenant::current());
    $this->assertFalse(app()->has($containerKey));
});

test('it can check if a current tenant has been set', function () {
    $this->assertFalse(Tenant::checkCurrent());

    $this->tenant->makeCurrent();

    $this->assertTrue(Tenant::checkCurrent());

    Tenant::forgetCurrent();

    $this->assertFalse(Tenant::checkCurrent());
});

test('it can check if a particular tenant is the current one', function () {
    /** @var \Spatie\Multitenancy\Models\Tenant $tenant */
    $tenant = Tenant::factory()->create();

    /** @var \Spatie\Multitenancy\Models\Tenant $anotherTenant */
    $anotherTenant = Tenant::factory()->create();

    $this->assertFalse($tenant->isCurrent());
    $this->assertFalse($anotherTenant->isCurrent());

    $tenant->makeCurrent();
    $this->assertTrue($tenant->isCurrent());
    $this->assertFalse($anotherTenant->isCurrent());

    $anotherTenant->makeCurrent();
    $this->assertFalse($tenant->isCurrent());
    $this->assertTrue($anotherTenant->isCurrent());

    Tenant::forgetCurrent();
    $this->assertFalse($tenant->isCurrent());
    $this->assertFalse($anotherTenant->isCurrent());
});

test('it will fire off events when making a tenant current', function () {
    Event::fake();

    Event::assertNotDispatched(MakingTenantCurrentEvent::class);
    Event::assertNotDispatched(MadeTenantCurrentEvent::class);

    $this->tenant->makeCurrent();

    Event::assertDispatched(MakingTenantCurrentEvent::class);
    Event::assertDispatched(MadeTenantCurrentEvent::class);
});

test('it will fire off events when forgetting the current tenant', function () {
    Event::fake();

    $this->tenant->makeCurrent();

    Event::assertNotDispatched(ForgettingCurrentTenantEvent::class);
    Event::assertNotDispatched(ForgotCurrentTenantEvent::class);

    Tenant::forgetCurrent();

    Event::assertDispatched(ForgettingCurrentTenantEvent::class);
    Event::assertDispatched(ForgotCurrentTenantEvent::class);
});

test('it will not fire off events when forgetting the current tenant when not current tenant is set', function () {
    Event::fake();

    Tenant::forgetCurrent();

    Event::assertNotDispatched(ForgettingCurrentTenantEvent::class);
    Event::assertNotDispatched(ForgotCurrentTenantEvent::class);
});

test('it will execute a callable and then restore the previous state', function () {
    Tenant::forgetCurrent();

    $this->assertNull(Tenant::current());

    $response = $this->tenant->execute(function (Tenant $tenant) {
        $this->assertEquals($tenant->id, Tenant::current()->id);

        return $tenant->id;
    });

    $this->assertNull(Tenant::current());

    $this->assertEquals($response, $this->tenant->id);
});

test('it will execute a delayed callback in tenant context', function () {
    Tenant::forgetCurrent();

    $this->assertNull(Tenant::current());

    $callback = $this->tenant->callback(function (Tenant $tenant) {
        $this->assertEquals($tenant->id, Tenant::current()->id);

        return $tenant->id;
    });

    $this->assertNull(Tenant::current());

    $response = $callback();

    $this->assertNull(Tenant::current());

    $this->assertEquals($response, $this->tenant->id);
});
