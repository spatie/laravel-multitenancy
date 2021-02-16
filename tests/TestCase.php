<?php

namespace Spatie\Multitenancy\Tests;

use Illuminate\Console\Application as Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Orchestra\Testbench\Concerns\WithLaravelMigrations;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\MultitenancyServiceProvider;
use Spatie\Multitenancy\Tests\Feature\Commands\TestClasses\TenantNoopCommand;

abstract class TestCase extends Orchestra
{
    use WithLaravelMigrations;

    public function setUp(): void
    {
        parent::setUp();

        $this->withFactories(__DIR__ . '/database/factories');

        $this->migrateDb();

        Tenant::truncate();

        DB::table('jobs')->truncate();

        View::addLocation(__DIR__ .'/stubs/views');
    }

    protected function getPackageProviders($app)
    {
        $this->bootCommands();

        return [
            MultitenancyServiceProvider::class,
        ];
    }

    protected function bootCommands() : self
    {
        Artisan::starting(function ($artisan) {
            $artisan->resolveCommands([
                TenantNoopCommand::class,
            ]);
        });

        return $this;
    }

    protected function migrateDb(): self
    {
        $landLordMigrationsPath = realpath(__DIR__ . '/database/migrations/landlord');
        $landLordMigrationsPath = str_replace('\\', '/', $landLordMigrationsPath);

        $this
            ->artisan("migrate --database=landlord --path={$landLordMigrationsPath} --realpath")
            ->assertExitCode(0);

        /*
        $tenantMigrationsPath = realpath(__DIR__ . '/database/migrations');
        $this
            ->artisan("migrate --database=tenant --path={$tenantMigrationsPath} --realpath")
            ->assertExitCode(0);
        */

        return $this;
    }

    public function getEnvironmentSetUp($app)
    {
        config(['database.default' => 'landlord']);

        config()->set('multitenancy.tenant_database_connection_name', 'tenant');

        config()->set('multitenancy.landlord_database_connection_name', 'landlord');

        config([
            'database.connections.landlord' => [
                'driver' => 'mysql',
                'username' => env('DB_USERNAME', 'root'),
                'host' => env('DB_HOST', '127.0.0.1'),
                'port' => env('DB_PORT', '3306'),
                'password' => env('DB_PASSWORD'),
                'database' => 'laravel_mt_landlord',
            ],

            'database.connections.tenant' => [
                'driver' => 'mysql',
                'username' => env('DB_USERNAME', 'root'),
                'host' => env('DB_HOST', '127.0.0.1'),
                'port' => env('DB_PORT', '3306'),
                'password' => env('DB_PASSWORD'),
                'database' => null,
            ],
        ]);

        config()->set('queue.default', 'database');

        config()->set('queue.connections.database', [
            'driver' => 'database',
            'table' => 'jobs',
            'queue' => 'default',
            'retry_after' => 90,
            'connection' => 'landlord',
        ]);
    }

    public function tempFile(string $fileName): string
    {
        return __DIR__ . "/temp/{$fileName}";
    }
}
