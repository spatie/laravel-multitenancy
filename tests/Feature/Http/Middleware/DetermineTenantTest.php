<?php

namespace Spatie\Multitenancy\Tests\Feature\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Mockery\MockInterface;
use Spatie\Multitenancy\Http\Middleware\DetermineTenant;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\TenantFinder\TenantFinder;
use Spatie\Multitenancy\Tests\TestCase;

class DetermineTenantTest extends TestCase
{
    protected Request $request;
    protected MockInterface $tenantFinderMock;
    protected MockInterface $tenantMock;

    public function setUp(): void
    {
        parent::setUp();
        // Create a dummy request.
        $this->request = $this->mock(Request::class);

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
    public function it_skips_tenant_finder_on_missing_tenant_finder_setting(): void
    {
        // Nullify the tenantFinder class setting.
        config()->set('multitenancy.tenant_finder', null);

        // Run middleware handle method.
        (new DetermineTenant())->handle($this->request, fn () => null);

        // Ensure findForRequest was never called.
        $this->tenantFinderMock->shouldNotHaveReceived('findForRequest');
    }

    /** @test */
    public function it_calls_tenant_finder(): void
    {
        // Run middleware handle method.
        (new DetermineTenant())->handle($this->request, fn () => null);

        // Ensure it hit findForRequest.
        $this->tenantFinderMock->shouldHaveReceived('findForRequest');
    }

    /** @test */
    public function it_gracefully_handles_tenant_not_found()
    {
        // Return a null tenant on find tenant
        $this->tenantFinderMock->shouldReceive('findForRequest')->andReturnNull();

        // Mock expected response, so we get exceptions if they do anything on it.
        $expectedResponseValue = $this->mock(Response::class);
        $passedRequest = null;

        // Run middleware handle method.
        $returnedValue = (new DetermineTenant())->handle(
            $this->request,
            function (Request $request) use (&$passedRequest, $expectedResponseValue) {
                $passedRequest = $request;

                return $expectedResponseValue;
            }
        );

        // Ensure request we passed in, matches the request passed to the callback.
        $this->assertSame($this->request, $passedRequest, "Returned request is same as passed request.");
        // Ensure request we passed in, matches the request returned from the callback.
        $this->assertSame($expectedResponseValue, $returnedValue, "Returned request is same as passed request.");
    }

    /** @test */
    public function it_makes_found_tenant_current()
    {
        // Run middleware handle method.
        (new DetermineTenant())->handle($this->request, fn () => null);

        // Ensure spy tenant had makeCurrent called on it.
        $this->tenantMock->shouldHaveReceived('makeCurrent');
    }
}
