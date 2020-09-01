<?php declare(strict_types=1);

namespace Spatie\Multitenancy\Tests\Feature\TenantFinder;

use Illuminate\Http\Request;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tests\TestCase;
use Spatie\Multitenancy\TenantFinder\SubdomainTenantFinder;

class SubdomainTenantFinderTest extends TestCase
{
    private SubdomainTenantFinder $tenantFinder;

    public function setUp(): void
    {
        parent::setUp();

        $this->tenantFinder = new SubdomainTenantFinder();
    }

    /** @test */
    public function it_can_find_a_tenant_for_the_current_subdomain()
    {
        $tenant = factory(Tenant::class)->create(['subdomain' => 'any-subdomain']);

        $request = Request::create('https://any-subdomain.test.test');

        $this->assertEquals($tenant->id, $this->tenantFinder->findForRequest($request)->id);
    }

    /** @test */
    public function it_will_return_null_if_there_are_no_tenants_for_sudomain()
    {
        $request = Request::create('https://any-subdomain.test.test');

        $this->assertNull($this->tenantFinder->findForRequest($request));
    }
}
