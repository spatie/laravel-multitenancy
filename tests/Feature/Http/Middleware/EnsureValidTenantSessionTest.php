<?php

use Illuminate\Support\Facades\Route;
use Spatie\Multitenancy\Http\Middleware\EnsureValidTenantSession;
use Spatie\Multitenancy\Models\Tenant;
use Symfony\Component\HttpFoundation\Response;

beforeEach(function () {
    Route::get('test-middleware', fn () => 'ok')->middleware(['web', EnsureValidTenantSession::class]);

    /** @var \Spatie\Multitenancy\Models\Tenant $tenant */
    $this->tenant = Tenant::factory()->create(['database' => 'laravel_mt_tenant_1']);

    $this->tenant->makeCurrent();
});

it('will set the tenant id if it has not been set', function () {
    expect(session('tenant_id'))->toBeNull();

    $this
        ->get('test-middleware')
        ->assertOk();

    expect(
        session('ensure_valid_tenant_session_tenant_id')
    )->toBe($this->tenant->id);
});

it('will allow requests for the tenant set in the session', function () {
    session()->put('ensure_valid_tenant_session_tenant_id', 1);

    $this
        ->get('test-middleware')
        ->assertOk();
});

it('will not allow requests for other tenants', function () {
    session()->put('ensure_valid_tenant_session_tenant_id', 2);

    $this
        ->get('test-middleware')
        ->assertStatus(Response::HTTP_UNAUTHORIZED);
});
