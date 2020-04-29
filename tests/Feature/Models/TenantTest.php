<?php

namespace Spatie\Multitenancy\Tests\Feature\Models;

use Illuminate\Foundation\Auth\User;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tests\TestCase;

class TenantTest extends TestCase
{
    /** @test */
    public function it_returns_the_current_tenant_and_list_of_its_users()
    {
        /** @var \Spatie\Multitenancy\Models\Tenant $tenant */
        $tenant = factory(Tenant::class)->create(['database' => 'laravel_mt_tenant_1']);

        $tenant->configure()->use();
        factory(User::class, 4)->create();
    }
}
