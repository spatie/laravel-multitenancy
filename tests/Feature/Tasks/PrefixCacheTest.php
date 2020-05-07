<?php

namespace Spatie\Multitenancy\Tests\Feature\Tasks;

use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tasks\PrefixCache;
use Spatie\Multitenancy\Tests\TestCase;

class PrefixCacheTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        config()->set('multitenancy.switch_tenant_tasks', [PrefixCache::class]);

        config()->set('cache.default', 'redis');

        cache()->flush();
    }

    /** @test */
    public function it_will_separate_the_cache()
    {
        /** @var \Spatie\Multitenancy\Models\Tenant $tenant */
        $tenant = factory(Tenant::class)->create();
        $tenant->makeCurrent();
        $this->assertFalse(cache()->has('key'));
        cache()->put('key', 'tenant-value');
        $this->assertEquals('tenant-value', cache('key'));

        /** @var \Spatie\Multitenancy\Models\Tenant $anotherTenant */
        $anotherTenant = factory(Tenant::class)->create();
        $anotherTenant->makeCurrent();
        $this->assertFalse(cache()->has('key'));
        cache()->put('key', 'another-tenant-value');

        $tenant->makeCurrent();
        $this->assertEquals('tenant-value', cache()->get('key'));

        $anotherTenant->makeCurrent();
        $this->assertEquals('another-tenant-value', cache()->get('key'));
    }
}
