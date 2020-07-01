<?php
namespace Spatie\Multitenancy;

use Spatie\Multitenancy\Models\Tenant;

class Landlord
{
    public static function execute(callable $callable)
    {
        $originalCurrentTenant = Tenant::current();

        Tenant::forgetCurrent();
        
        $result = $callable();
        
        optional($originalCurrentTenant)->makeCurrent();
        
        return $result;
    }
}
