<?php

namespace Spatie\Multitenancy\Tests\Feature\Tasks;

use Illuminate\Support\Facades\Cache;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tasks\PrefixCacheTask;
use Spatie\Multitenancy\Tests\TestCase;
use Illuminate\Contracts\Cache\Repository as CacheContract;

class PrefixCacheTaskTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        config()->set('multitenancy.switch_tenant_tasks', [PrefixCacheTask::class]);

        config()->set('cache.default', 'redis');

        cache()->flush();
    }


    /** @test */
    public function it_will_separate_the_cache_prefix_for_each_tenant()
    {
        $originalPrefix = config('cache.prefix').':';
        $this->assertEquals($originalPrefix, cache()->getStore()->getPrefix());
        $this->assertEquals($originalPrefix, app(CacheContract::class)->getPrefix());

        /** @var \Spatie\Multitenancy\Models\Tenant $tenantOne */
        $tenantOne = Tenant::factory()->create();
        $tenantOne->makeCurrent();
        $tenantOnePrefix = 'tenant_id_'.$tenantOne->id.':';

        $this->assertEquals($tenantOnePrefix, cache()->getStore()->getPrefix());
        $this->assertEquals($tenantOnePrefix, app(CacheContract::class)->getPrefix());

        /** @var \Spatie\Multitenancy\Models\Tenant $tenantOne */
        $tenantTwo = Tenant::factory()->create();
        $tenantTwo->makeCurrent();
        $tenantTwoPrefix = 'tenant_id_'.$tenantTwo->id.':';
        $this->assertEquals($tenantTwoPrefix, cache()->getStore()->getPrefix());
        $this->assertEquals($tenantTwoPrefix, app(CacheContract::class)->getPrefix());
    }

    /** @test */
    public function it_will_separate_the_cache_for_each_tenant()
    {
        cache()->put('key', 'cache-landlord');

        /** @var \Spatie\Multitenancy\Models\Tenant $tenantOne */
        $tenantOne = Tenant::factory()->create();
        $tenantOne->makeCurrent();
        $this->assertFalse(cache()->has('key'));
        cache()->put('key', 'tenant-one');

        /** @var \Spatie\Multitenancy\Models\Tenant $tenantTwo */
        $tenantTwo = Tenant::factory()->create();
        $tenantTwo->makeCurrent();
        $this->assertFalse(cache()->has('key'));
        cache()->put('key', 'tenant-two');

        $tenantOne->makeCurrent();
        $this->assertEquals('tenant-one', cache()->get('key'));

        $tenantTwo->makeCurrent();
        $this->assertEquals('tenant-two', cache()->get('key'));

        Tenant::forgetCurrent();
        $this->assertEquals('cache-landlord', cache()->get('key'));
    }
}
