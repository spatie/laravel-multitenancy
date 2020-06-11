---
title: Ensuring a current tenant has been set
weight: 1
---

In your project you probably will have many routes where you expect a tenant has been made current.

You can ensure that a current tenant has been set by applying the `\Spatie\Multitenancy\Http\Middleware\NeedsTenant` middleware on those routes.

We recommend registering this middleware in a group alongside `\Spatie\Multitenancy\Http\Middleware\EnsureValidTenantSession`, to also verify that the session is not being abused across multiple tenants.

```php
// in `app\Http\Kernel.php`

protected $middlewareGroups = [
    // ...
    'tenant' => [
        \Spatie\Multitenancy\Http\Middleware\NeedsTenant::class,
        \Spatie\Multitenancy\Http\Middleware\EnsureValidTenantSession::class
    ]
];
```

With the middleware registered, you can use it in routes files (or in a route service provider).

```php
// in a routes file

Route::middleware('tenant')->group(function() {
    // routes
})
```

If the request does not have a "current" tenant for these routes, an `Spatie\Multitenancy\Exceptions\NoCurrentTenant` exception will be thrown. You can listen for that exception in [the exception handler](https://laravel.com/docs/master/errors#the-exception-handler). You could set some kind of flash message and redirect to a login page there.
