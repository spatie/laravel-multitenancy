<?php

namespace Spatie\Multitenancy\Tests\Feature\Models;

use Illuminate\Support\Facades\Event;
use Spatie\Multitenancy\Events\ForgettingCurrentTenantEvent;
use Spatie\Multitenancy\Events\ForgotCurrentTenantEvent;
use Spatie\Multitenancy\Events\MadeTenantCurrentEvent;
use Spatie\Multitenancy\Events\MakingTenantCurrentEvent;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tests\TestCase;

class TenantTest extends TestCase
{
    private Tenant $tenant;

    public function setUp(): void
    {
        parent::setUp();

        $this->tenant = factory(Tenant::class)->create();
    }

    /** @test */
    public function it_can_get_the_current_tenant()
    {
        $this->assertNull(Tenant::current());

        $this->tenant->makeCurrent();

        $this->assertEquals($this->tenant->id, Tenant::current()->id);
    }

    /** @test */
    public function it_will_bind_the_current_tenant_in_the_container()
    {
        $containerKey = config('multitenancy.current_tenant_container_key');

        $this->assertFalse(app()->has($containerKey));

        $this->tenant->makeCurrent();

        $this->assertTrue(app()->has($containerKey));

        $this->assertInstanceOf(Tenant::class, app($containerKey));
        $this->assertEquals($this->tenant->id, app($containerKey)->id);
    }

    /** @test */
    public function it_can_forget_the_current_tenant()
    {
        $containerKey = config('multitenancy.current_tenant_container_key');

        $this->tenant->makeCurrent();
        $this->assertEquals($this->tenant->id, Tenant::current()->id);
        $this->assertTrue(app()->has($containerKey));

        Tenant::forgetCurrent();
        $this->assertNull(Tenant::current());
        $this->assertFalse(app()->has($containerKey));
    }

    /** @test */
    public function it_can_check_if_a_current_tenant_has_been_set()
    {
        $this->assertFalse(Tenant::checkCurrent());

        $this->tenant->makeCurrent();

        $this->assertTrue(Tenant::checkCurrent());

        Tenant::forgetCurrent();

        $this->assertFalse(Tenant::checkCurrent());
    }

    /** @test */
    public function it_can_check_if_the_a_particular_tenant_is_the_current_one()
    {
        /** @var \Spatie\Multitenancy\Models\Tenant $tenant */
        $tenant = factory(Tenant::class)->create();

        /** @var \Spatie\Multitenancy\Models\Tenant $anotherTenant */
        $anotherTenant = factory(Tenant::class)->create();

        $this->assertFalse($tenant->isCurrent());
        $this->assertFalse($anotherTenant->isCurrent());

        $tenant->makeCurrent();
        $this->assertTrue($tenant->isCurrent());
        $this->assertFalse($anotherTenant->isCurrent());

        $anotherTenant->makeCurrent();
        $this->assertFalse($tenant->isCurrent());
        $this->assertTrue($anotherTenant->isCurrent());

        Tenant::forgetCurrent();
        $this->assertFalse($tenant->isCurrent());
        $this->assertFalse($anotherTenant->isCurrent());
    }

    /** @test */
    public function it_will_fire_off_events_when_making_a_tenant_current()
    {
        Event::fake();

        Event::assertNotDispatched(MakingTenantCurrentEvent::class);
        Event::assertNotDispatched(MadeTenantCurrentEvent::class);

        $this->tenant->makeCurrent();

        Event::assertDispatched(MakingTenantCurrentEvent::class);
        Event::assertDispatched(MadeTenantCurrentEvent::class);
    }

    /** @test */
    public function it_will_fire_off_events_when_forgetting_the_current_tenant()
    {
        Event::fake();

        $this->tenant->makeCurrent();

        Event::assertNotDispatched(ForgettingCurrentTenantEvent::class);
        Event::assertNotDispatched(ForgotCurrentTenantEvent::class);

        Tenant::forgetCurrent();

        Event::assertDispatched(ForgettingCurrentTenantEvent::class);
        Event::assertDispatched(ForgotCurrentTenantEvent::class);
    }

    /** @test */
    public function it_will_not_fire_off_events_when_forgetting_the_current_tenant_when_not_current_tenant_is_set()
    {
        Event::fake();

        Tenant::forgetCurrent();

        Event::assertNotDispatched(ForgettingCurrentTenantEvent::class);
        Event::assertNotDispatched(ForgotCurrentTenantEvent::class);
    }
}
