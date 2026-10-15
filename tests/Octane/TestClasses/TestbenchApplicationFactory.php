<?php

namespace Spatie\Multitenancy\Tests\Octane\TestClasses;

use Illuminate\Foundation\Application;
use Laravel\Octane\ApplicationFactory;
use Spatie\Multitenancy\Tests\OctaneTestCase;

class TestbenchApplicationFactory extends ApplicationFactory
{
    public function __construct(
        protected OctaneTestCase $testCase,
        string $basePath,
    ) {
        parent::__construct($basePath);
    }

    public function createApplication(array $initialInstances = []): Application
    {
        $app = $this->testCase->createApplication();

        foreach ($initialInstances as $key => $value) {
            $app->instance($key, $value);
        }

        if ($app->routesAreCached()) {
            require $app->getCachedRoutesPath();
        }

        return $this->warm($app);
    }
}
