---
title: Switching route cache paths
weight: 3
---

Laravel comes with [route caching](https://laravel.com/docs/master/routing#route-caching) out of the box. By default
all routes are cached, which means that the application will only load the routes once. This is great if your routes
are static. However, if you're using dynamic routes, for example different routes for different tenants, you'll need
to keep a separate route cache for each tenant.

The `Spatie\Multitenancy\Tasks\SwitchRouteCacheTask` can switch the configured `APP_ROUTES_CACHE` environment variable to a tenant specific value: `bootstrap/cache/routes-v7-tenant-{$tenant->id}.php`.

To use this task, you should uncomment it in the `switch_tenant_tasks` section of the `multitenancy` config file.

```php
// in config/multitenancy.php

'switch_tenant_tasks' => [
    \Spatie\Multitenancy\Tasks\SwitchRouteCacheTask::class,
    // other tasks
],
```

Finally but **most importantly**, you should use `php artisan tenant:artisan route:cache` to cache your routes instead of Laravel's default `route:cache` command. This will make sure a different route cache file is generated for each tenant.
