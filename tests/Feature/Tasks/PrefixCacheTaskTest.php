<?php declare(strict_types=1);

namespace Spatie\Multitenancy\Tests\Feature\Tasks;

use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tests\TestCase;
use Spatie\Multitenancy\Tasks\PrefixCacheTask;

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
        cache()->put('key', 'original-value');

        /** @var Tenant $tenant */
        $tenant = factory(Tenant::class)->create();
        $tenant->makeCurrent();
        $this->assertFalse(cache()->has('key'));
        cache()->put('key', 'tenant-value');

        /** @var Tenant $anotherTenant */
        $anotherTenant = factory(Tenant::class)->create();
        $anotherTenant->makeCurrent();
        $this->assertFalse(cache()->has('key'));
        cache()->put('key', 'another-tenant-value');

        $tenant->makeCurrent();
        $this->assertEquals('tenant-value', cache()->get('key'));

        $anotherTenant->makeCurrent();
        $this->assertEquals('another-tenant-value', cache()->get('key'));

        Tenant::forgetCurrent();
        $this->assertEquals('original-value', cache()->get('key'));
    }
}
