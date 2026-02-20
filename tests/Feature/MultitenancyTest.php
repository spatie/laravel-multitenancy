<?php

use Spatie\Multitenancy\Events\TenantNotFoundForRequestEvent;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Multitenancy;
use Spatie\Multitenancy\TenantFinder\DomainTenantFinder;

it('does not determine tenant in console by default', function () {
   Tenant::factory()->create(['domain' => 'example.com']);

    config()->set('multitenancy.tenant_finder', DomainTenantFinder::class);

    $this->app->instance('request', Request::create('https://example.com'));

    app(Multitenancy::class)->start();

    expect(Tenant::current())->toBeNull();
});

it('determines tenant in tests when determine_current_tenant_in_tests is true', function () {
    $tenant = Tenant::factory()->create(['domain' => 'example.com']);

    config()->set('multitenancy.tenant_finder', DomainTenantFinder::class);
    config()->set('multitenancy.determine_current_tenant_in_tests', true);

    $this->app->instance('request', Request::create('https://example.com'));

    app(Multitenancy::class)->start();

    expect(Tenant::current()->id)->toEqual($tenant->id);
});

it('fires TenantNotFoundForRequestEvent when determine_current_tenant_in_tests is true and no tenant matches', function () {
    config()->set('multitenancy.tenant_finder', DomainTenantFinder::class);
    config()->set('multitenancy.determine_current_tenant_in_tests', true);

    $this->app->instance('request', Request::create('https://unknown-domain.com'));

    Event::fake();

    app(Multitenancy::class)->start();

    Event::assertDispatched(TenantNotFoundForRequestEvent::class);
});

it('does not determine tenant in tests when determine_current_tenant_in_tests is false', function () {
    $tenant = Tenant::factory()->create(['domain' => 'example.com']);

    config()->set('multitenancy.tenant_finder', DomainTenantFinder::class);
    config()->set('multitenancy.determine_current_tenant_in_tests', false);

    $this->app->instance('request', Request::create('https://example.com'));

    app(Multitenancy::class)->start();

    expect(Tenant::current())->toBeNull();
});
