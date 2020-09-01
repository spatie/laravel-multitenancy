<?php

namespace Spatie\Multitenancy\Tests\Feature\TenantFinder;

use Illuminate\Http\Request;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tests\TestCase;
use Spatie\Multitenancy\TenantFinder\SubdomainTenantFinder;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SubdomainTenantFinderTest extends TestCase
{
    private SubdomainTenantFinder $tenantFinder;

    public function setUp(): void
    {
        parent::setUp();

        $this->tenantFinder = new SubdomainTenantFinder();
    }

    /** @test */
    public function it_can_find_a_tenant_for_the_current_subdomain_if_landlord_domain()
    {
        $tenant = factory(Tenant::class)->create(['subdomain' => 'any-subdomain']);

        $request = Request::create('https://any-subdomain.landlord.domain');

        $this->assertEquals($tenant->id, $this->tenantFinder->findForRequest($request)->id);
    }

    /** @test */
    public function it_will_return_null_if_there_are_no_tenants_for_sudomain_if_landlord_domain()
    {
        $request = Request::create('https://any-subdomain.landlord.domain');

        $this->assertNull($this->tenantFinder->findForRequest($request));
    }

    /** @test */
    public function it_will_throw_exception_if_not_landlord_domain_and_has_subdomain()
    {
        $this->expectException(NotFoundHttpException::class);

        $request = Request::create('https://any-subdomain.not-landlord.domain');
        $this->tenantFinder->findForRequest($request);
    }
}
