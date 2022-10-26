<?php

use Illuminate\Support\Facades\Event;
use Spatie\Multitenancy\Events\ForgettingCurrentTenantEvent;
use Spatie\Multitenancy\Events\ForgotCurrentTenantEvent;
use Spatie\Multitenancy\Events\MadeTenantCurrentEvent;
use Spatie\Multitenancy\Events\MakingTenantCurrentEvent;
use Spatie\Multitenancy\Models\Tenant;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
});

test('it can get the current tenant', function () {
    expect(Tenant::current())->toBeNull();

    $this->tenant->makeCurrent();

    expect(Tenant::current()->id)->toEqual($this->tenant->id);
});

test('it will bind the current tenant in the container', function () {
    $containerKey = config('multitenancy.current_tenant_container_key');

    expect(app()->has($containerKey))->toBeFalse();

    $this->tenant->makeCurrent();

    expect(app()->has($containerKey))->toBeTrue();

    expect(app($containerKey))->toBeInstanceOf(Tenant::class);
    expect(app($containerKey)->id)->toEqual($this->tenant->id);
});

test('it can forget the current tenant', function () {
    $containerKey = config('multitenancy.current_tenant_container_key');

    $this->tenant->makeCurrent();
    expect(Tenant::current()->id)->toEqual($this->tenant->id);
    expect(app()->has($containerKey))->toBeTrue();

    Tenant::forgetCurrent();
    expect(Tenant::current())->toBeNull();
    expect(app())->has($containerKey)->toBeFalse();
});

test('it can check if a current tenant has been set', function () {
    expect(Tenant::checkCurrent())->toBeFalse();

    $this->tenant->makeCurrent();

    expect(Tenant::checkCurrent())->toBeTrue();

    Tenant::forgetCurrent();

    expect(Tenant::checkCurrent())->toBeFalse();
});

test('it can check if a particular tenant is the current one', function () {
    /** @var \Spatie\Multitenancy\Models\Tenant $tenant */
    $tenant = Tenant::factory()->create();

    /** @var \Spatie\Multitenancy\Models\Tenant $anotherTenant */
    $anotherTenant = Tenant::factory()->create();

    expect($tenant->isCurrent())->toBeFalse()
        ->and($anotherTenant->isCurrent())->toBeFalse();

    $tenant->makeCurrent();
    expect($tenant->isCurrent())->toBeTrue()
        ->and($anotherTenant->isCurrent())->toBeFalse();

    $anotherTenant->makeCurrent();
    expect($tenant->isCurrent())->toBeFalse()
        ->and($anotherTenant->isCurrent())->toBeTrue();

    Tenant::forgetCurrent();
    expect($tenant->isCurrent())->toBeFalse()
        ->and($anotherTenant->isCurrent())->toBeFalse();
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

    expect(Tenant::current())->toBeNull();

    $response = $this->tenant->execute(function (Tenant $tenant) {
        expect(Tenant::current()->id)->toEqual($tenant->id);

        return $tenant->id;
    });

    expect(Tenant::current())->toBeNull();

    expect($this->tenant->id)->toEqual($response);
});

test('it will execute a delayed callback in tenant context', function () {
    Tenant::forgetCurrent();

    expect(Tenant::current())->toBeNull();

    $callback = $this->tenant->callback(function (Tenant $tenant) {
        expect(Tenant::current()->id)->toEqual($tenant->id);

        return $tenant->id;
    });
    expect(Tenant::current())->toBeNull();

    $response = $callback();

    expect(Tenant::current())->toBeNull();

    expect($this->tenant->id)->toBe($response);
});
