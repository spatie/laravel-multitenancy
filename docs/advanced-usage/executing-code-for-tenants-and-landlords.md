---
title: Executing code for tenants and landlords
weight: 9
---

The `Tenant` and `Landlord` models provide an `execute` method that allows you to execute code for a specific tenant or landlord.

## Executing tenant code in landlord request

To execute tenant code in a landlord request, you can use the method `execute` available in the `Tenant` model.

Here is an example where we flush the cache for a tenant using our landlord API:

```php
Route::delete('/api/{tenant}/flush-cache', function (Tenant $tenant) {
    $result = $tenant->execute(fn (Tenant $tenant) => cache()->flush());
   
    return json_encode(["success" => $result]);
});
```

Inside the closure passed to `execute`, the given `$tenant` is set as the current one.

Here's another example, where a job is dispatched from a landlord API route:

```php
Route::post('/api/{tenant}/reminder', function (Tenant $tenant) {
    return json_encode([ 
        'data' => $tenant->execute(fn () => dispatch(ExpirationReminder())),
    ]);
});
```
### Executing a delayed callback in the correct Tenant context
If you need to define a callback that will be executed in the correct Tenant context every time it is called, you can use the Tenant's `callback` method.
A notable example for this is the use in the Laravel scheduler where you can loop through all the tenants and schedule callbacks to be executed at the given time:

```php
protected function schedule(Schedule $schedule)
{
    Tenant::all()->eachCurrent(function(Tenant $tenant) use ($schedule) {
        $schedule->run($tenant->callback(fn() => cache()->flush()))->daily();
    });
}
```

## Executing landlord code in tenant request

To execute landlord code, from inside a tenant request, you can use the method `execute` on `Spatie\Multitenancy\Landlord`. 

Here is an example where we will first clear the tenant cache, and next, the landlord cache:

```php
use  Spatie\Multitenancy\Landlord;

// ...

Tenant::first()->execute(function (Tenant $tenant) {
    // it will clear the tenant cache
    Artisan::call('cache:clear'); 
   
    // it will clear the landlord cache
    Landlord::execute(fn () => Artisan::call('cache:clear')); 
});
```

Inside the closure passed to `execute`, the landlord is made active by forgetting the current tenant.

## Testing with DatabaseTransactions for Tenant

When performing testing and using `DatabaseTransactions` trait, the default setup in Laravel requires changes to ensure that the transactions are performed on the `Tenant` connection. Accordingly, the default `TestCase.php` file may be updated as below:

```php
namespace Tests;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Event;
use Spatie\Multitenancy\Concerns\UsesMultitenancyConfig;
use Spatie\Multitenancy\Events\MadeTenantCurrentEvent;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, DatabaseTransactions, UsesMultitenancyConfig;

    protected function connectionsToTransact()
    {
        return [
            $this->landlordDatabaseConnectionName(),
            $this->tenantDatabaseConnectionName(),
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        Event::listen(MadeTenantCurrentEvent::class, function () {
            $this->beginDatabaseTransaction();
        });
    }
}
```

In case a user login is performed using the `Auth` facade in the `setUp` method on a test, the tenant switching will not happen automatically. Accordingly, the `setUp` method above may be updated as below to ensure that the required tenant has been set (using the first `Tenant` as an example below)

```php

protected function setUp(): void
{
    parent::setUp();

    Event::listen(MadeTenantCurrentEvent::class, function () {
        $this->beginDatabaseTransaction();
    });
    
    Tenant::first()->makeCurrent();
}
```
