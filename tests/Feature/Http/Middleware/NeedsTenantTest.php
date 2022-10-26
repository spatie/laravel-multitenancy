<?php

use Illuminate\Support\Facades\Route;
use Spatie\Multitenancy\Exceptions\NoCurrentTenant;
use Spatie\Multitenancy\Http\Middleware\NeedsTenant;
use Spatie\Multitenancy\Models\Tenant;

beforeEach(function () {
    $this->withoutExceptionHandling();

    Route::get('middleware-test', fn () => 'ok')
        ->middleware(NeedsTenant::class);

    $this->tenant = Tenant::factory()->create();
});

test('it will pass if there is current tenant set', function () {
    $this->tenant->makeCurrent();

    $this->get('middleware-test')->assertOk();
});

test('it will throw an exception when there is not current tenant')
    ->get('middleware-test')
    ->throws(NoCurrentTenant::class);
