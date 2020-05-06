<?php

namespace Spatie\Multitenancy\Exceptions;

use Exception;

class InvalidConfiguration extends Exception
{
    /** @test */
    public static function tenantConnectionDoesNotExist(string $expectedConnectionName): self
    {
        return new static("Could not find a tenant connection named `{$expectedConnectionName}`. Make sure to create a connection with that name in the `connections` key of the `database` config file.");
    }

    /** @test */
    public static function landlordConnectionDoesNotExist(string $expectedConnectionName): self
    {
        return new static("Could not find a landlord connection named `{$expectedConnectionName}`. Make sure to create a connection with that name in the `connections` key of the `database` config file.");
    }
}
