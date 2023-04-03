<?php

namespace Spatie\Multitenancy;

use Spatie\Multitenancy\Models\Tenant;

class Landlord
{
    public static function execute(callable $callable)
    {
        $originalCurrentTenant = Tenant::current();

        try {
            Tenant::forgetCurrent();

            $result = $callable();
        } finally {
            if ($originalCurrentTenant) {
                $originalCurrentTenant->makeCurrent();
            }
        }

        return $result;
    }
}
