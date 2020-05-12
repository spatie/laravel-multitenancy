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

    public static function invalidAction(string $actionName, string $configuredClass, string $actionClass): self
    {
        return new static("The class currently specified in the `multitenancy.actions.{$actionName}` key '{$configuredClass}' should be or extend `{$actionClass}`.");
    }
}
