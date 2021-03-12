<?php

namespace Spatie\Multitenancy\Tests\Feature\Http\Middleware;

use Illuminate\Support\Facades\Route;
use Spatie\Multitenancy\Exceptions\NoCurrentTenant;
use Spatie\Multitenancy\Http\Middleware\NeedsTenant;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tests\TestCase;

class NeedsTenantTest extends TestCase
{
    protected Tenant $tenant;

    public function setUp(): void
    {
        parent::setUp();

        $this->withoutExceptionHandling();

        Route::get('middleware-test', fn () => 'ok')->middleware(NeedsTenant::class);

        $this->tenant = factory(Tenant::class)->create();
    }

    /** @test */
    public function it_will_pass_if_there_is_current_tenant_set()
    {
        $this->tenant->makeCurrent();

        $this->get('middleware-test')->assertOk();
    }

    /** @test */
    public function it_will_throw_an_exception_when_there_is_not_current_tenant()
    {
        $this->expectException(NoCurrentTenant::class);

        $this->get('middleware-test');
    }
}
