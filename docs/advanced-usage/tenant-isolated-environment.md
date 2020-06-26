---
title: Tenant isolated environment
weight: 9
---

To execute tenant-code, without setting it as current, you can use the method `execute` available in the `Tenant` model. It will create an isolated environment valid only for the callable code.

Here is an example where we flush the cache for a tenant using our landlord API:

```php
Route::delete('/api/{tenant}/flush-cache', static function (Tenant $tenant) {
    $result = $tenant->execute(fn (Tenant $tenant) => cache()->flush());
   
    return json_encode([ "success" => $result ]);
});
```

Another scenario is when you need to work with a tenant, but you have already another tenant as the current one:

```php
$currentTenant = \Spatie\Multitenancy\Models\Tenant::where('domain', 'example-tenant-1.spatie.be')->first();
$currentTenant->makeCurrent();
cache()->set('used_at', '1987-02-21');

$beta_used_at = \Spatie\Multitenancy\Models\Tenant::query()
    ->where('domain', 'example-tenant-2.spatie.be')
    ->first()
    ->execute(function (Tenant $tenant) {
        return tap('2020-02-21', fn ($used_at) => cache()->set('used_at', $used_at));
    }); 
  
echo cache()->get('used_at') . PHP_EOL; // Returns: '1987-02-21'
echo $beta_used_at . PHP_EOL; // Returns: '2020-02-21'
```

Here's a final example, where a job is dispatched from our landlord API route:
```php
Route::post('/api/{tenant}/reminder', function (Tenant $tenant) {
    return json_encode([ 
        'data' => $tenant->run(fn () => dispatch(ExpirationReminder())),
    ]);
});
```
