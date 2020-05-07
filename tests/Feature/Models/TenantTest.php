<?php

namespace Spatie\Multitenancy\Tests\Feature\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Spatie\Multitenancy\Events\MadeTenantCurrentEvent;
use Spatie\Multitenancy\Events\MakingTenantCurrentEvent;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tasks\SwitchTenantDatabase;
use Spatie\Multitenancy\Tests\TestCase;

class TenantTest extends TestCase
{
    private Tenant $tenant;

    public function setUp(): void
    {
        parent::setUp();

        $this->tenant = factory(Tenant::class)->create(['database' => 'laravel_mt_tenant_1']);

        config()->set('multitenancy.switch_tenant_tasks', [SwitchTenantDatabase::class]);
    }

    /** @test */
    public function when_making_a_tenant_current_it_will_perform_the_tasks()
    {
        $this->assertNull(DB::connection('tenant')->getDatabaseName());

        $this->tenant->makeCurrent();

        $this->assertEquals('laravel_mt_tenant_1', DB::connection('tenant')->getDatabaseName());
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
    public function it_will_fire_off_events_when_making_a_tenant_current()
    {
        Event::fake();

        Event::assertNotDispatched(MakingTenantCurrentEvent::class);
        Event::assertNotDispatched(MadeTenantCurrentEvent::class);

        $this->tenant->makeCurrent();

        Event::assertDispatched(MakingTenantCurrentEvent::class);
        Event::assertDispatched(MadeTenantCurrentEvent::class);
    }
}
