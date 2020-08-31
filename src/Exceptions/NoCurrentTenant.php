<?php declare(strict_types=1);

namespace Spatie\Multitenancy\Exceptions;

use Exception;

class NoCurrentTenant extends Exception
{
    public static function make(): self
    {
        return new static('The request expected a current tenant but none was set.');
    }
}
