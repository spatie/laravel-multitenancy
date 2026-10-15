<?php

namespace Spatie\Multitenancy\Tests\Feature\Tasks\TestClasses;

use Spatie\Multitenancy\Tasks\SwitchRouteCacheTask;

class SwitchRouteCacheWithoutReinitializingRouterTask extends SwitchRouteCacheTask
{
    protected function shouldReinitializeRouter(): bool
    {
        return false;
    }
}
