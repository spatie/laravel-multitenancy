<?php

namespace Spatie\Multitenancy;

use Spatie\Multitenancy\Contracts\IsTenant;

class Landlord
{
    public static function execute(callable $callable)
    {
        $originalCurrentTenant = app(IsTenant::class)::current();

        app(IsTenant::class)::forgetCurrent();

        $result = $callable();

        $originalCurrentTenant?->makeCurrent();

        return $result;
    }
}
