<?php

namespace Spatie\Multitenancy\Tests\Feature\Tasks;

use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tasks\PrefixCacheTask;
use Spatie\Multitenancy\Tests\TestCase;

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
