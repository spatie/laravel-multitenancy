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

        app()->forgetInstance('cache');

        app()->forgetInstance('cache.store');
    }


    /** @test */
    public function it_will_separate_the_cache_prefix_for_each_tenant()
    {
        $originalPrefix = config('cache.prefix').':';
        $this->assertEquals($originalPrefix, cache()->getStore()->getPrefix());
        $this->assertEquals($originalPrefix, app('cache')->getPrefix());
        $this->assertEquals($originalPrefix, app(CacheContract::class)->getPrefix());
        $this->assertEquals($originalPrefix, app('cache.store')->getPrefix());

        /** @var \Spatie\Multitenancy\Models\Tenant $tenantOne */
        $tenantOne = Tenant::factory()->create();
        $tenantOne->makeCurrent();
        $tenantOnePrefix = 'tenant_id_'.$tenantOne->id.':';

        $this->assertEquals($tenantOnePrefix, cache()->getStore()->getPrefix());
        $this->assertEquals($tenantOnePrefix, app('cache')->getPrefix());
        $this->assertEquals($tenantOnePrefix, app(CacheContract::class)->getPrefix());
        $this->assertEquals($tenantOnePrefix, app('cache.store')->getPrefix());

        /** @var \Spatie\Multitenancy\Models\Tenant $tenantOne */
        $tenantTwo = Tenant::factory()->create();
        $tenantTwo->makeCurrent();
        $tenantTwoPrefix = 'tenant_id_'.$tenantTwo->id.':';
        $this->assertEquals($tenantTwoPrefix, cache()->getStore()->getPrefix());
        $this->assertEquals($tenantTwoPrefix, app('cache')->getPrefix());
        $this->assertEquals($tenantTwoPrefix, app(CacheContract::class)->getPrefix());
        $this->assertEquals($tenantTwoPrefix, app('cache.store')->getPrefix());
    }

    /** @test */
    public function it_will_separate_the_cache_for_each_tenant()
    {
        cache()->put('key', 'cache-landlord');

        /** @var \Spatie\Multitenancy\Models\Tenant $tenantOne */
        $tenantOne = Tenant::factory()->create();
        $tenantOne->makeCurrent();
        $tenantOneVal = 'tenant-'.$tenantOne->domain;
        $this->assertFalse(cache()->has('key'));
        cache()->put('key', $tenantOneVal);

        /** @var \Spatie\Multitenancy\Models\Tenant $tenantTwo */
        $tenantTwo = Tenant::factory()->create();
        $tenantTwo->makeCurrent();
        $tenantTwoVal = 'tenant-'.$tenantTwo->domain;
        $this->assertFalse(cache()->has('key'));
        cache()->put('key', $tenantTwoVal);

        $tenantOne->makeCurrent();
        $this->assertEquals($tenantOneVal, cache()->get('key'));
        $this->assertEquals($tenantOneVal, app('cache')->get('key'));
        $this->assertEquals($tenantOneVal, app(CacheContract::class)->get('key'));
        $this->assertEquals($tenantOneVal, app('cache.store')->get('key'));

        $tenantTwo->makeCurrent();
        $this->assertEquals($tenantTwoVal, cache()->get('key'));
        $this->assertEquals($tenantTwoVal, app('cache')->get('key'));
        $this->assertEquals($tenantTwoVal, app(CacheContract::class)->get('key'));
        $this->assertEquals($tenantTwoVal, app('cache.store')->get('key'));

        Tenant::forgetCurrent();
        $this->assertEquals('cache-landlord', cache()->get('key'));
    }
}
