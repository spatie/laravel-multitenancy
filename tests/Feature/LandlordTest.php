<?php
namespace Spatie\Multitenancy\Tests\Feature;

use Spatie\Multitenancy\Landlord;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tests\TestCase;

class LandlordTest extends TestCase
{
    private Tenant $tenant;

    public function setUp(): void
    {
        parent::setUp();

        $this->tenant = factory(Tenant::class)->create();
    }

    /** @test */
    public function it_will_execute_a_callable_as_landlord_and_then_restore_the_previous_tenant()
    {
        $this->tenant->makeCurrent();

        $response = Landlord::execute(fn () => Tenant::current());

        $this->assertNull($response);

        $this->assertEquals($this->tenant->id, Tenant::current()->id);
    }
}
