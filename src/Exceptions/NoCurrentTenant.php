<?php

namespace Spatie\Multitenancy\Exceptions;

use Exception;

class NoCurrentTenant extends Exception
{
    public static function make()
    {
        return new static('The request expected a current tenant but none was set.');
    }
}
