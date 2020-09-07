---
title: Looping over a collection of tenants
weight: 2
---

Whenever you fetch tenants using an eloquent query, you'll get returned an instance of `Spatie\Multitenancy\TenantCollection`. This class extends from `Illuminate\Database\Eloquent\Collection` so you can use any of regular collection methods that you know and love.

In addition to the regular methods, `TenantCollection` provides two extra methods: `eachCurrent` and `mapCurrent`. Both these methods work like the regular `each` and `map` methods, but in addition they will automatically make the tenant the current one.

```php
Tenant::all()->eachCurrent(function(Tenant $tenant) {
    // the passed tenant has been made current
    Tenant::current() === $tenant->id // returns true;
})
```
