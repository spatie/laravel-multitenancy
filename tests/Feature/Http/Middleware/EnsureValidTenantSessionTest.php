<?php

namespace Spatie\Multitenancy\Tests\Feature\Http\Middleware;

use Illuminate\Support\Facades\Route;
use Spatie\Multitenancy\Http\Middleware\EnsureValidTenantSession;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tests\TestCase;
use Symfony\Component\HttpFoundation\Response;

class EnsureValidTenantSessionTest extends TestCase
{
    protected Tenant $tenant;

    public function setUp(): void
    {
        parent::setUp();

        Route::get('test-middleware', function() {
            return 'ok';
        })->middleware(['web', EnsureValidTenantSession::class]);

        /** @var Tenant $tenant */
        $this->tenant = factory(Tenant::class)->create(['database' => 'laravel_mt_tenant_1']);

        $this->tenant->configure()->makeCurrent();
    }


    /** @test */
    public function it_will_set_the_tenant_id_if_it_has_not_been_set()
    {
        $this->assertNull(session('tenant_id'));

        $this
            ->get('test-middleware')
            ->assertOk();

        $this->assertNotNull(session('tenant_id'));
        $this->assertEquals($this->tenant->id, session('tenant_id'));
    }

    /** @test */
    public function it_will_allow_requests_for_the_tenant_set_in_the_session()
    {
        session()->put('tenant_id', 1);

        $this
            ->get('test-middleware')
            ->assertOk();
    }

    /** @test */
    public function it_will_not_allow_requests_for_other_tenants()
    {
        session()->put('tenant_id', 2);

        $this
            ->get('test-middleware')
            ->assertStatus(Response::HTTP_UNAUTHORIZED);
    }
}
