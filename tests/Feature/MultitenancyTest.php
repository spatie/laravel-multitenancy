<?php

use Illuminate\Support\Facades\Event;
use Spatie\Multitenancy\Events\TenantNotFoundForRequestEvent;
use Spatie\Multitenancy\Multitenancy;

beforeEach(function () {
    $this->multitenancy = new Multitenancy(app());
});

it('will fire a TenantNotFoundForRequestEvent when no tenant was found by request', function () {
    Event::fake();

    Event::assertNotDispatched(TenantNotFoundForRequestEvent::class);

    $this->multitenancy->determineCurrentTenant();

    Event::assertDispatched(TenantNotFoundForRequestEvent::class);
});
