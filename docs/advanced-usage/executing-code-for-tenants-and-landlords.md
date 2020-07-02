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
   
    return json_encode([ "success" => $result ]);
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
