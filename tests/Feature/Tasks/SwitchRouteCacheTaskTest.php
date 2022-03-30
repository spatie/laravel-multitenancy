<?php

namespace Spatie\Multitenancy\Tests\Feature\Tasks;

use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tasks\SwitchRouteCacheTask;
use Spatie\Multitenancy\Tests\TestCase;

class SwitchRouteCacheTaskTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        config()->set('multitenancy.switch_tenant_tasks', [SwitchRouteCacheTask::class]);
    }

    /** @test */
    public function it_will_use_a_different_routes_cache_environment_variable_for_each_tenant(): void
    {
        /** @var \Spatie\Multitenancy\Models\Tenant $tenant */
        $tenant = Tenant::factory()->create();
        $tenant->makeCurrent();
        $this->assertEquals("bootstrap/cache/routes-v7-tenant-{$tenant->id}.php", env('APP_ROUTES_CACHE'));

        /** @var \Spatie\Multitenancy\Models\Tenant $anotherTenant */
        $anotherTenant = Tenant::factory()->create();
        $anotherTenant->makeCurrent();
        $this->assertEquals("bootstrap/cache/routes-v7-tenant-{$anotherTenant->id}.php", env('APP_ROUTES_CACHE'));

        $tenant->makeCurrent();
        $this->assertEquals("bootstrap/cache/routes-v7-tenant-{$tenant->id}.php", env('APP_ROUTES_CACHE'));

        $anotherTenant->makeCurrent();
        $this->assertEquals("bootstrap/cache/routes-v7-tenant-{$anotherTenant->id}.php", env('APP_ROUTES_CACHE'));

        Tenant::forgetCurrent();
        $this->assertNull(env('APP_ROUTES_CACHE'));
    }

    /** @test */
    public function it_will_use_a_shared_routes_cache_environmnet_variable_for_all_tenants(): void
    {
        config()->set('multitenancy.shared_routes_cache', true);

        /** @var \Spatie\Multitenancy\Models\Tenant $tenant */
        $tenant = Tenant::factory()->create();
        $tenant->makeCurrent();
        $this->assertEquals("bootstrap/cache/routes-v7-tenants.php", env('APP_ROUTES_CACHE'));

        /** @var \Spatie\Multitenancy\Models\Tenant $anotherTenant */
        $anotherTenant = Tenant::factory()->create();
        $anotherTenant->makeCurrent();
        $this->assertEquals("bootstrap/cache/routes-v7-tenants.php", env('APP_ROUTES_CACHE'));

        Tenant::forgetCurrent();
        $this->assertNull(env('APP_ROUTES_CACHE'));
    }
}
