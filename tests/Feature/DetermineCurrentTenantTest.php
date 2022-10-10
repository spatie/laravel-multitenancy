<?php

namespace Spatie\Multitenancy\Tests\Feature\Http\Middleware;

use Illuminate\Http\Request;
use Mockery\MockInterface;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Multitenancy;
use Spatie\Multitenancy\TenantFinder\TenantFinder;
use Spatie\Multitenancy\Tests\TestCase;

class DetermineCurrentTenantTest extends TestCase
{
    protected MockInterface $tenantFinderMock;
    protected MockInterface $tenantMock;

    public function setUp(): void
    {
        parent::setUp();

        // Set dumy configuration for tenantFinder.
        config()->set('multitenancy.tenant_finder', TenantFinder::class);

        // Setup mock tenant
        $this->tenantMock = $this->mock(Tenant::class);
        $this->tenantMock->shouldReceive('makeCurrent')->andReturnSelf();

        // Setup a mock to check usage of TenantFinder.
        $this->tenantFinderMock = $this->mock(TenantFinder::class);
        $this->tenantFinderMock->shouldReceive('findForRequest')
            ->withArgs([\Mockery::type(Request::class)])
            ->andReturns($this->tenantMock);
    }

    /** @test */
    public function it_will_find_tenant_from_middleware_request()
    {
        $request = $this->mock(Request::class);

        // Run determineCurrentTenant with our request.
        app(Multitenancy::class)->determineCurrentTenant($request);

        // Ensure it hit findForRequest with the request we provided.
        $this->tenantFinderMock->shouldHaveReceived('findForRequest')->with($request);
    }

    /** @test */
    public function it_makes_tenant_found_from_middleware_current()
    {
        $request = $this->mock(Request::class);

        // Run determineCurrentTenant with our request.
        app(Multitenancy::class)->determineCurrentTenant($request);

        // Ensure it made tenant current.
        $this->tenantMock->shouldHaveReceived('makeCurrent');
    }
}
