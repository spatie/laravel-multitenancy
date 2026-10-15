<?php

namespace Spatie\Multitenancy\Tests;

use Illuminate\Http\Request;
use Illuminate\Support\Env;
use Laravel\Octane\Contracts\Client;
use Laravel\Octane\CurrentApplication;
use Laravel\Octane\Listeners\ReportException;
use Laravel\Octane\OctaneServiceProvider;
use Laravel\Octane\RequestContext;
use Laravel\Octane\Testing\Fakes\FakeClient;
use Laravel\Octane\Worker;
use Spatie\Multitenancy\Tasks\SwitchRouteCacheTask;
use Spatie\Multitenancy\Tasks\SwitchTenantDatabaseTask;
use Spatie\Multitenancy\TenantFinder\DomainTenantFinder;
use Spatie\Multitenancy\Tests\Octane\TestClasses\TestbenchApplicationFactory;
use Symfony\Component\HttpFoundation\Response;

abstract class OctaneTestCase extends TestCase
{
    protected FakeClient $octaneClient;

    protected Worker $octaneWorker;

    protected function setUp(): void
    {
        $_SERVER['LARAVEL_OCTANE'] = 1;

        parent::setUp();
    }

    protected function tearDown(): void
    {
        if (isset($this->octaneWorker)) {
            $this->octaneWorker->terminate();
        }

        CurrentApplication::set($this->app);

        foreach (glob($this->app->bootstrapPath('cache/routes-v7*.php')) as $cachedRoutesFile) {
            unlink($cachedRoutesFile);
        }

        Env::getRepository()->clear('APP_ROUTES_CACHE');

        parent::tearDown();

        unset($_SERVER['LARAVEL_OCTANE'], $_ENV['APP_RUNNING_IN_CONSOLE']);
    }

    protected function getPackageProviders($app)
    {
        return [
            OctaneServiceProvider::class,
            ...parent::getPackageProviders($app),
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app->instance(Client::class, new FakeClient([]));

        $app->extend(ReportException::class, fn () => new class () extends ReportException {
            public function handle($event): void
            {
            }
        });

        config()->set('multitenancy.tenant_finder', DomainTenantFinder::class);

        config()->set('multitenancy.switch_tenant_tasks', [
            SwitchRouteCacheTask::class,
            SwitchTenantDatabaseTask::class,
        ]);
    }

    protected function bootOctaneWorker(?FakeClient $client = null): Worker
    {
        $_ENV['APP_RUNNING_IN_CONSOLE'] = false;

        $this->octaneClient = $client ?? new FakeClient([]);

        $this->octaneWorker = new Worker(
            new TestbenchApplicationFactory($this, $this->app->basePath()),
            $this->octaneClient,
        );

        $this->octaneWorker->boot();

        return $this->octaneWorker;
    }

    protected function handleOctaneRequest(string $url): ?Response
    {
        $request = Request::create($url);

        $respondedBefore = count($this->octaneClient->responses);

        $this->octaneWorker->handle($request, new RequestContext(['request' => $request]));

        return $this->octaneClient->responses[$respondedBefore] ?? null;
    }
}
