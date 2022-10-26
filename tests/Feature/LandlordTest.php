<?php

use Spatie\Multitenancy\Landlord;
use Spatie\Multitenancy\Models\Tenant;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
});

test('it will execute a callable as landlord and then restore the previous tenant', function () {
    $this->tenant->makeCurrent();

    $response = Landlord::execute(fn () => Tenant::current());

    expect($response)->toBeNull();

    expect($this->tenant->id)->toEqual(Tenant::current()->id);
});
