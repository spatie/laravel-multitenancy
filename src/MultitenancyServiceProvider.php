<?php

namespace Spatie\Multitenancy;

use Illuminate\Events\Dispatcher;
use Laravel\Octane\Events\RequestReceived as OctaneRequestReceived;
use Laravel\Octane\Events\RequestTerminated as OctaneRequestTerminated;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\Multitenancy\Commands\TenantsArtisanCommand;
use Spatie\Multitenancy\Concerns\UsesMultitenancyConfig;
use Spatie\Multitenancy\Models\Concerns\UsesTenantModel;

class MultitenancyServiceProvider extends PackageServiceProvider
{
    use UsesTenantModel;
    use UsesMultitenancyConfig;

    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-multitenancy')
            ->hasConfigFile()
            ->hasMigration('landlord/create_landlord_tenants_table')
            ->hasCommand(TenantsArtisanCommand::class);
    }

    public function boot(): void
    {
        $this->app->singleton(Multitenancy::class, function ($app) {
            return new Multitenancy($app);
        });

        $dispatcher = app(Dispatcher::class);

        if (!env('LARAVEL_OCTANE')) {
            app(Multitenancy::class)->start();
        } else {
            $dispatcher->listen(OctaneRequestReceived::class, function () {
                app(Multitenancy::class)->start();
            });

            $dispatcher->listen(OctaneRequestTerminated::class, function () {
                app(Multitenancy::class)->end();
            });
        }
    }
}
