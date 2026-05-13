---
name: laravel-multitenancy-development
description: Build and work with Spatie Laravel Multitenancy features, including tenant finders, the current tenant, switch tasks, multi-database setups, tenant-aware queues and artisan commands.
---

# Laravel Multitenancy Development

## When to use this skill

Use this skill when working with multi-tenant Laravel applications using `spatie/laravel-multitenancy`: determining the current tenant per request, isolating databases or caches per tenant, making queued jobs and artisan commands tenant-aware, or designing landlord/tenant migration strategies.

## Core Concepts

- **Intentionally minimal**: the package resolves a current tenant and runs tasks on switch — it does not add global query scopes or model isolation by itself.
- **Current tenant** is bound in the IoC container under the key `currentTenant` and written to Laravel `Context` under the key `tenantId`.
- A **`TenantFinder`** resolves the tenant from the current HTTP request (e.g. by domain).
- **`SwitchTenantTask`** classes mutate the environment when a tenant becomes current (switch DB, prefix cache, etc.) and restore it when forgotten.
- Models on the landlord DB use `UsesLandlordConnection`; models on the tenant DB use `UsesTenantConnection`.

## Setup

```bash
composer require spatie/laravel-multitenancy
php artisan vendor:publish --provider="Spatie\Multitenancy\MultitenancyServiceProvider" --tag="multitenancy-config"
php artisan vendor:publish --provider="Spatie\Multitenancy\MultitenancyServiceProvider" --tag="multitenancy-migrations"
```

Register middleware in `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \Spatie\Multitenancy\Http\Middleware\NeedsTenant::class,
        \Spatie\Multitenancy\Http\Middleware\EnsureValidTenantSession::class,
    ]);
})
```

## Configuring a Tenant Finder

Set the finder class in `config/multitenancy.php`:

```php
'tenant_finder' => \Spatie\Multitenancy\TenantFinder\DomainTenantFinder::class,
```

`DomainTenantFinder` looks up the tenant by matching `$request->getHost()` against a `domain` column on the tenants table.

To use a custom finder, extend `TenantFinder` and implement `findForRequest`:

```php
use Illuminate\Http\Request;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\TenantFinder\TenantFinder;

class SubdomainTenantFinder extends TenantFinder
{
    public function findForRequest(Request $request): ?IsTenant
    {
        $subdomain = explode('.', $request->getHost())[0];

        return app(IsTenant::class)::whereSubdomain($subdomain)->first();
    }
}
```

## Working with the Current Tenant

```php
use Spatie\Multitenancy\Models\Tenant;

// Make a tenant current (fires events, runs tasks)
$tenant->makeCurrent();

// Read the current tenant
Tenant::current();        // returns ?Tenant
app('currentTenant');     // same, via container

// Check and forget
Tenant::checkCurrent();   // bool
$tenant->isCurrent();     // bool
Tenant::forgetCurrent();  // runs forget tasks, returns the tenant
```

## Executing Code for a Tenant or Landlord

`execute()` makes the tenant current, runs the callable, then restores the previous state:

```php
$result = $tenant->execute(function (Tenant $tenant) {
    return cache()->get('stats');
});
```

`callback()` returns a closure — useful for the scheduler:

```php
$schedule->call($tenant->callback(fn () => cache()->flush()))->daily();
```

To run code **outside** any tenant context, use `Landlord`:

```php
use Spatie\Multitenancy\Landlord;

Landlord::execute(function () {
    Artisan::call('cache:clear');
});
```

`TenantCollection` adds iteration helpers: `eachCurrent`, `mapCurrent`, `filterCurrent`, `rejectCurrent`.

```php
Tenant::all()->eachCurrent(function (Tenant $tenant) {
    cache()->flush();
});
```

## Multi-Database Setup

Define a `tenant` connection (with `database => null`) and a `landlord` connection in `config/database.php`:

```php
'connections' => [
    'tenant' => [
        'driver'   => 'mysql',
        'database' => null,
        'host'     => '127.0.0.1',
        'username' => 'root',
        'password' => '',
    ],

    'landlord' => [
        'driver'   => 'mysql',
        'database' => 'name_of_landlord_db',
        'host'     => '127.0.0.1',
        'username' => 'root',
        'password' => '',
    ],
],
```

Set the connection names in `config/multitenancy.php`:

```php
'tenant_database_connection_name'   => 'tenant',
'landlord_database_connection_name' => 'landlord',
```

Apply the correct connection trait to every Eloquent model:

```php
// Models whose table lives in the tenant DB
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class Post extends Model
{
    use UsesTenantConnection;
}

// Models whose table lives in the landlord DB
use Spatie\Multitenancy\Models\Concerns\UsesLandlordConnection;

class Tenant extends Model
{
    use UsesLandlordConnection;
}
```

## Switch Tenant Tasks

Tasks run every time `makeCurrent()` or `forgetCurrent()` is called. Register them in `config/multitenancy.php`:

```php
'switch_tenant_tasks' => [
    \Spatie\Multitenancy\Tasks\SwitchTenantDatabaseTask::class,
    // \Spatie\Multitenancy\Tasks\PrefixCacheTask::class,
    // \Spatie\Multitenancy\Tasks\SwitchRouteCacheTask::class,
],
```

Built-in tasks:

- **`SwitchTenantDatabaseTask`** — sets the `tenant` connection's `database` to `$tenant->database` and purges the connection. Required for multi-DB.
- **`PrefixCacheTask`** — overrides `cache.prefix` to `tenant_{$tenant->id}`. Works with memory-based stores (Redis, APC).
- **`SwitchRouteCacheTask`** — switches `APP_ROUTES_CACHE` to a per-tenant file (`bootstrap/cache/routes-v7-tenant-{id}.php`), or a shared file when `'shared_routes_cache' => true`.

To create a custom task, implement `SwitchTenantTask`:

```php
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\Tasks\SwitchTenantTask;

class SwitchStorageDiskTask implements SwitchTenantTask
{
    public function makeCurrent(IsTenant $tenant): void
    {
        config(['filesystems.disks.s3.bucket' => $tenant->bucket]);
    }

    public function forgetCurrent(): void
    {
        config(['filesystems.disks.s3.bucket' => config('filesystems.default_bucket')]);
    }
}
```

Tasks can receive constructor parameters via array config:

```php
'switch_tenant_tasks' => [
    \App\Tasks\YourTask::class => ['key' => 'value'],
],
```

## Middleware

- **`NeedsTenant`** — aborts the request (throws `NoCurrentTenant`) if no tenant is current. Apply to all tenant routes.
- **`EnsureValidTenantSession`** — stores the first-seen tenant ID in the session and aborts with 401 if a different tenant ID is detected later. Prevents session cross-contamination.

## Custom Tenant Model

Set `tenant_model` in `config/multitenancy.php` and point it to your own class:

```php
'tenant_model' => \App\Models\Tenant::class,
```

To use an existing model (e.g. a Jetstream `Team`) as a tenant, implement `IsTenant` with the `ImplementsTenant` trait:

```php
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\Models\Concerns\ImplementsTenant;
use Spatie\Multitenancy\Models\Concerns\UsesLandlordConnection;

class Team extends JetstreamTeam implements IsTenant
{
    use UsesLandlordConnection;
    use ImplementsTenant;
}
```

Use a `creating` hook to provision a database when a tenant is created:

```php
protected static function booted(): void
{
    static::creating(fn (Tenant $tenant) => $tenant->createDatabase());
}
```

## Migrations & Seeding

**Landlord** migrations live in `database/migrations/landlord`. Run them once:

```bash
php artisan migrate --path=database/migrations/landlord --database=landlord
```

**Tenant** migrations run for every tenant via `tenants:artisan`:

```bash
php artisan tenants:artisan "migrate --database=tenant"
php artisan tenants:artisan "migrate --database=tenant --seed" --tenant=123
```

In seeders, branch on `Tenant::checkCurrent()`:

```php
public function run(): void
{
    Tenant::checkCurrent()
        ? $this->runTenantSpecificSeeders()
        : $this->runLandlordSpecificSeeders();
}
```

Programmatic migrations use `MigrateTenantAction`:

```php
use Spatie\Multitenancy\Actions\MigrateTenantAction;

app(MigrateTenantAction::class)->fresh()->seed()->execute($tenant);
```

## Artisan Commands

`tenants:artisan` loops over all tenants (or the specified ones) and runs a command for each:

```bash
php artisan tenants:artisan "migrate --database=tenant"
php artisan tenants:artisan "cache:clear" --tenant=1 --tenant=2
```

To make your own commands tenant-aware, add the `TenantAware` concern and a `{--tenant=*}` option:

```php
use Illuminate\Console\Command;
use Spatie\Multitenancy\Commands\Concerns\TenantAware;

class SendReports extends Command
{
    use TenantAware;

    protected $signature = 'reports:send {--tenant=*}';

    public function handle(): void
    {
        $this->line('Sending for tenant: ' . Tenant::current()->name);
    }
}
```

Omitting `--tenant` runs the command for every tenant. The command instance is reused across tenants — reset any state at the top of `handle()`.

## Tenant-Aware Queues

Enable globally in `config/multitenancy.php`:

```php
'queues_are_tenant_aware_by_default' => true,
```

Or mark individual jobs with the `TenantAware` interface:

```php
use Illuminate\Contracts\Queue\ShouldQueue;
use Spatie\Multitenancy\Jobs\TenantAware;

class ProcessReport implements ShouldQueue, TenantAware
{
    public function handle(): void { /* ... */ }
}
```

Opt out per job with `NotTenantAware`:

```php
use Spatie\Multitenancy\Jobs\NotTenantAware;

class SyncGlobalData implements ShouldQueue, NotTenantAware
{
    public function handle(): void { /* ... */ }
}
```

Or list classes in config:

```php
'tenant_aware_jobs'     => [\App\Jobs\ProcessReport::class],
'not_tenant_aware_jobs' => [\App\Jobs\SyncGlobalData::class],
```

For closures dispatched to the queue, pass the tenant explicitly:

```php
$tenant = Tenant::current();

dispatch(function () use ($tenant) {
    $tenant->execute(function () {
        // tenant context is active here
    });
});
```

If a tenant-aware job fires but the tenant cannot be resolved, `CurrentTenantCouldNotBeDeterminedInTenantAwareJob` is thrown and the job is deleted from the queue.

## Events

All events live in the `Spatie\Multitenancy\Events` namespace and carry `public IsTenant $tenant` except where noted:

| Event | When |
|---|---|
| `MakingTenantCurrentEvent` | Before switch tasks run |
| `MadeTenantCurrentEvent` | After switch tasks + container binding |
| `ForgettingCurrentTenantEvent` | Before forget tasks run |
| `ForgotCurrentTenantEvent` | After forget tasks + container cleared |
| `TenantNotFoundForRequestEvent` | When the finder returns `null` (carries `Request $request`) |

## Performance

- Switch tasks run synchronously on every `makeCurrent()` / `forgetCurrent()` call — keep them fast.
- `shared_routes_cache` avoids generating one routes file per tenant when routes are identical across tenants.
- Octane is supported out of the box: the service provider hooks into `RequestReceived` / `RequestTerminated` events automatically when `LARAVEL_OCTANE` is set.
- The current tenant is stored in Laravel `Context` (`tenantId`), which queue workers read to restore tenant state before processing a job.
