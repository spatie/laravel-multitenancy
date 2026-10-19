<?php

namespace Spatie\Multitenancy\Tests\Octane\TestClasses;

use Spatie\Multitenancy\Tasks\SwitchRouteCacheTask;
use Spatie\Multitenancy\Tests\Feature\Tasks\TestClasses\RegisterTenantRoutesTask;

trait UsesRegisterTenantRoutesTask
{
    public function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('multitenancy.switch_tenant_tasks', [
            SwitchRouteCacheTask::class,
            RegisterTenantRoutesTask::class,
        ]);
    }
}
