<?php

namespace Spatie\Multitenancy;

use Illuminate\Support\Facades\Event;
use Laravel\Octane\Events\RequestReceived as OctaneRequestReceived;
use Laravel\Octane\Events\RequestTerminated as OctaneRequestTerminated;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\Multitenancy\Commands\TenantsArtisanCommand;
use Spatie\Multitenancy\Concerns\UsesMultitenancyConfig;
use Spatie\Multitenancy\Models\Concerns\UsesTenantModel;

class MultitenancyServiceProvider extends PackageServiceProvider
{
    use UsesTenantModel,
        UsesMultitenancyConfig;

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
        $this->app->bind(Multitenancy::class, fn ($app) => new Multitenancy($app));

        if (! isset($_SERVER['LARAVEL_OCTANE'])) {
            $this->app[Multitenancy::class]->start();

            return;
        }

        Event::listen(fn (OctaneRequestReceived $requestReceived) => app(Multitenancy::class)->start());
        Event::listen(fn (OctaneRequestTerminated $requestTerminated) => app(Multitenancy::class)->end());
    }
}
