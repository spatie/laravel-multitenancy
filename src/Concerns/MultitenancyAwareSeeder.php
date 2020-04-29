<?php

namespace Spatie\Multitenancy\Concerns;

trait MultitenancyAwareSeeder
{
    public function isSeedingLandlordDb(): bool
    {
        return $_SERVER['argv'][1] != 'tenants:migrate';
    }
}
