<?php

namespace Spatie\Multitenancy;

use Illuminate\Support\Facades\Event;
use Laravel\Octane\Events\RequestReceived as OctaneRequestReceived;
use Laravel\Octane\Events\RequestTerminated as OctaneRequestTerminated;
use Laravel\Octane\Events\TaskTerminated as OctaneTaskTerminated;
use Laravel\Octane\Events\TickTerminated as OctaneTickTerminated;
use Laravel\Octane\Events\WorkerErrorOccurred as OctaneWorkerErrorOccurred;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\Multitenancy\Commands\TenantsArtisanCommand;
use Spatie\Multitenancy\Concerns\UsesMultitenancyConfig;
use Spatie\Multitenancy\Contracts\IsTenant;

class MultitenancyServiceProvider extends PackageServiceProvider
{
    use UsesMultitenancyConfig;

    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-multitenancy')
            ->hasConfigFile()
            ->hasMigration('landlord/create_landlord_tenants_table')
            ->hasCommand(TenantsArtisanCommand::class);
    }

    public function packageBooted(): void
    {
        $this->app->bind(IsTenant::class, config('multitenancy.tenant_model'));

        $this->app->bind(Multitenancy::class, fn ($app) => new Multitenancy($app));

        app(Multitenancy::class)->configureQueue();

        $this->detectsLaravelOctane();
    }

    protected function detectsLaravelOctane(): static
    {
        if (! isset($_SERVER['LARAVEL_OCTANE'])) {
            app(Multitenancy::class)->start();

            return $this;
        }

        app(Multitenancy::class)->registerBindings();

        $this->app->booted(function () {
            Event::listen(fn (OctaneRequestReceived $requestReceived) => app(Multitenancy::class)->start());
            Event::listen(fn (OctaneRequestTerminated $requestTerminated) => app(Multitenancy::class)->end());
            Event::listen(fn (OctaneTaskTerminated $taskTerminated) => app(Multitenancy::class)->end());
            Event::listen(fn (OctaneTickTerminated $tickTerminated) => app(Multitenancy::class)->end());
            Event::listen(fn (OctaneWorkerErrorOccurred $workerErrorOccurred) => app(Multitenancy::class)->end());
        });

        return $this;
    }
}
