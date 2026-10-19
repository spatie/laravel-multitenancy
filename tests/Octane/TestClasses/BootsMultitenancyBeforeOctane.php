<?php

namespace Spatie\Multitenancy\Tests\Octane\TestClasses;

use Laravel\Octane\OctaneServiceProvider;
use Spatie\Multitenancy\MultitenancyServiceProvider;

trait BootsMultitenancyBeforeOctane
{
    protected function getPackageProviders($app)
    {
        return [
            MultitenancyServiceProvider::class,
            OctaneServiceProvider::class,
        ];
    }
}
