<?php

namespace Spatie\Multitenancy\Tests;

use Illuminate\Foundation\Auth\User;
use Orchestra\Testbench\Concerns\WithLaravelMigrations;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\MultitenancyServiceProvider;

abstract class TestCase extends Orchestra
{
    use WithLaravelMigrations;

    public function setUp(): void
    {
        parent::setUp();

        $this->withFactories(__DIR__ . '/Database/factories');

        $this->migrateDb();

        Tenant::truncate();

        User::truncate();
    }

    protected function getPackageProviders($app)
    {
        return [
            MultitenancyServiceProvider::class,
        ];
    }

    protected function migrateDb(): self
    {
        $landLordMigrationsPath = realpath(__DIR__ . '/database/migrations/landlord');

        $this
            ->artisan("migrate --database=landlord --path={$landLordMigrationsPath} --realpath")
            ->assertExitCode(0);

        $tenantMigrationsPath = realpath(__DIR__ . '/database/migrations');
        $this
            ->artisan("migrate --database=tenant --path={$tenantMigrationsPath} --realpath")
            ->assertExitCode(0);

        return $this;
    }

    public function getEnvironmentSetUp($app)
    {
        config(['database.default' => 'landlord']);

        config(['database.default' => 'tenant']);

        config([
            'database.connections.landlord' => [
                'driver' => 'mysql',
                'username' => 'root',
                'host' => '127.0.1',
                'password' => '',
                'database' => 'laravel_mt_landlord',
            ],

            'database.connections.tenant' => [
                'driver' => 'mysql',
                'username' => 'root',
                'host' => '127.0.1',
                'password' => '',
                'database' => 'laravel_mt_tenant_1',
            ],
        ]);

        config()->set('app.key', '6rE9Nz59bGRbeMATftriyQjrpF7DcOQm');
    }
}
