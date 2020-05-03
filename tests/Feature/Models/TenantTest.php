<?php

namespace Spatie\Multitenancy\Tests\Feature\Models;

use Illuminate\Foundation\Auth\User;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tests\TestCase;

class TenantTest extends TestCase
{
    private Tenant $tenant;

    public function setUp(): void
    {
        parent::setUp();

        $this->tenant = factory(Tenant::class)->create(['database' => 'laravel_mt_tenant_1']);
    }

    /** @test */
    public function it_returns_the_current_tenant_and_list_of_its_users()
    {
        $this->tenant->makeCurrent();
        factory(User::class, 4)->create();
    }

    /** @test */
    public function it_can_get_the_current_tenant()
    {
        $this->assertNull(Tenant::current());

        $this->tenant->makeCurrent();

        $this->assertEquals($this->tenant->id, Tenant::current()->id);
    }
}
