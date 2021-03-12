<?php

namespace Spatie\Multitenancy\Tests\Feature\TenantFinder;

use Illuminate\Http\Request;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\TenantFinder\DomainTenantFinder;
use Spatie\Multitenancy\Tests\TestCase;

class DomainTenantFinderTest extends TestCase
{
    protected DomainTenantFinder $tenantFinder;

    public function setUp(): void
    {
        parent::setUp();

        $this->tenantFinder = new DomainTenantFinder();
    }

    /** @test */
    public function it_can_find_a_tenant_for_the_current_domain()
    {
        $tenant = factory(Tenant::class)->create(['domain' => 'my-domain.com']);

        $request = Request::create('https://my-domain.com');

        $this->assertEquals($tenant->id, $this->tenantFinder->findForRequest($request)->id);
    }

    /** @test */
    public function it_will_return_null_if_there_are_no_tenants()
    {
        $request = Request::create('https://my-domain.com');

        $this->assertNull($this->tenantFinder->findForRequest($request));
    }

    /** @test */
    public function it_will_return_null_if_no_tenant_can_be_found_for_the_current_domain()
    {
        $tenant = factory(Tenant::class)->create(['domain' => 'my-domain.com']);

        $request = Request::create('https://another-domain.com');

        $this->assertNull($this->tenantFinder->findForRequest($request));
    }
}
