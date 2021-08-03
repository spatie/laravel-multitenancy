<?php

namespace Spatie\Multitenancy\Tests\Feature;

use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\TenantCollection;
use Spatie\Multitenancy\Tests\TestCase;

class TenantCollectionTest extends TestCase
{
    protected TenantCollection $tenants;

    public function setUp(): void
    {
        parent::setUp();

        factory(Tenant::class, 3)->create();

        $this->tenants = Tenant::get();
    }

    /** @test */
    public function it_can_make_each_tenant_current()
    {
        $this->tenants->eachCurrent(function (Tenant $tenant) {
            $this->assertEquals($tenant->id, Tenant::current()->id);
        });
    }

    /** @test */
    public function after_making_each_tenant_current_the_original_current_tenant_is_made_current_again()
    {
        $this->assertFalse(Tenant::checkCurrent());

        $this->tenants->eachCurrent(function (Tenant $tenant) {
        });

        $this->assertFalse(Tenant::checkCurrent());

        $this->tenants[1]->makeCurrent();

        $this->tenants->eachCurrent(function (Tenant $tenant) {
        });

        $this->assertTrue($this->tenants[1]->isCurrent());
    }

    /** @test */
    public function it_can_map_while_making_each_tenant_current()
    {
        $tenantIds = $this->tenants
            ->mapCurrent(function (Tenant $tenant) {
                $this->assertEquals($tenant->id, Tenant::current()->id);

                return $tenant->id;
            })
            ->toArray();

        $this->assertEquals([1, 2, 3], $tenantIds);
    }

    /** @test */
    public function after_mapping_each_current_tenant_the_original_current_tenant_is_made_current_again()
    {
        $this->assertFalse(Tenant::checkCurrent());

        $this->tenants->mapCurrent(function (Tenant $tenant) {
        });

        $this->assertFalse(Tenant::checkCurrent());

        $this->tenants[1]->makeCurrent();

        $this->tenants->mapCurrent(function (Tenant $tenant) {
        });

        $this->assertTrue($this->tenants[1]->isCurrent());
    }

    /** @test */
    public function it_can_filter_while_making_each_tenant_current()
    {
        $tenantIds = $this->tenants
            ->filterCurrent(function (Tenant $tenant) {
                $this->assertEquals($tenant->id, Tenant::current()->id);

                return $tenant->id != 2;
            })
            ->pluck('id')
            ->toArray();

        $this->assertEquals([1, 3], $tenantIds);
    }

    /** @test */
    public function after_filtering_each_current_tenant_the_original_current_tenant_is_made_current_again()
    {
        $this->assertFalse(Tenant::checkCurrent());

        $this->tenants->filterCurrent(function (Tenant $tenant) {
        });

        $this->assertFalse(Tenant::checkCurrent());

        $this->tenants[1]->makeCurrent();

        $this->tenants->filterCurrent(function (Tenant $tenant) {
        });

        $this->assertTrue($this->tenants[1]->isCurrent());
    }

    /** @test */
    public function it_can_reject_while_making_each_tenant_current()
    {
        $tenantIds = $this->tenants
            ->rejectCurrent(function (Tenant $tenant) {
                $this->assertEquals($tenant->id, Tenant::current()->id);

                return $tenant->id == 2;
            })
            ->pluck('id')
            ->toArray();

        $this->assertEquals([1, 3], $tenantIds);
    }

    /** @test */
    public function after_rejecting_each_current_tenant_the_original_current_tenant_is_made_current_again()
    {
        $this->assertFalse(Tenant::checkCurrent());

        $this->tenants->rejectCurrent(function (Tenant $tenant) {
        });

        $this->assertFalse(Tenant::checkCurrent());

        $this->tenants[1]->makeCurrent();

        $this->tenants->rejectCurrent(function (Tenant $tenant) {
        });

        $this->assertTrue($this->tenants[1]->isCurrent());
    }
}
