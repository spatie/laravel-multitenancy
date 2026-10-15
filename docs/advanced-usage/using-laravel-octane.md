---
title: Using Laravel Octane
weight: 10
---

[Laravel Octane](https://laravel.com/docs/octane) keeps your application in memory and serves many requests from the same worker. The package detects Octane through the `LARAVEL_OCTANE` environment variable that Octane sets for its workers, and adapts its behaviour. There is nothing to configure.

## The lifecycle of a request

In a PHP-FPM application the current tenant is determined when the application boots, and the whole environment is thrown away when the request ends. Inside an Octane worker the application boots once, so the package hooks into the events Octane dispatches for every request instead:

- when Octane dispatches `RequestReceived`, the package determines the current tenant with the configured tenant finder and makes it current;
- when Octane dispatches `RequestTerminated`, the package forgets the current tenant, so the switch tenant tasks restore the landlord environment before the worker picks up its next request;
- when Octane dispatches `WorkerErrorOccurred`, because the request failed before it could terminate, for instance when the client disconnected while the response was being sent, the package forgets the current tenant as well.

If a switch tenant task throws an exception while a tenant is being made current, the tasks that already ran are asked to forget the tenant again before the exception is rethrown. A failed switch leaves nothing behind in the worker. When a task throws while the tenant is being forgotten, the remaining tasks still forget it, and the exception is rethrown afterwards.

## What a worker shares between requests

Octane gives every request its own copy of the service container and discards it when the request ends. Some objects are shared by all the requests a worker handles anyway: the router, the database manager and the environment repository among them. This is why the switch tenant tasks matter more with Octane than with PHP-FPM: `forgetCurrent` is the only thing that restores the landlord environment.

The tasks that ship with the package take care of this:

- [`SwitchTenantDatabaseTask`](/docs/laravel-multitenancy/v4/using-tasks-to-prepare-the-environment/switching-databases/) purges the tenant connection and refreshes the connection resolver of Eloquent on every switch;
- [`PrefixCacheTask`](/docs/laravel-multitenancy/v4/using-tasks-to-prepare-the-environment/prefixing-cache/) rebuilds the cache store on every switch, so the prefix of the previous tenant is never reused;
- [`SwitchRouteCacheTask`](/docs/laravel-multitenancy/v4/using-tasks-to-prepare-the-environment/switching-route-cache-paths/) loads the route cache of the tenant into the shared router, and loads the route cache of the landlord again when the tenant is forgotten. When the landlord routes are not cached, the routes the router held before the first switch are put back instead.

The `octane` cache driver is the exception: Octane creates its store once when the worker boots and that store ignores the cache prefix, so `PrefixCacheTask` cannot separate tenants there. Keep tenant data in another store, like Redis.

## Writing your own tasks

When [creating your own task](/docs/laravel-multitenancy/v4/using-tasks-to-prepare-the-environment/creating-your-own-task/), make sure `forgetCurrent` undoes everything `makeCurrent` changed. In a long running process nothing else restores the environment for the landlord.

Inside an Octane worker the tasks are instantiated the first time a tenant is made current in a request, and reused until that request ends. Storing the original values as properties, like the tasks of the package do, keeps working.

## Queues

Octane only serves HTTP requests. Jobs are still processed by regular queue workers, where the package [makes the queue tenant aware](/docs/laravel-multitenancy/v4/basic-usage/making-queues-tenant-aware/) in the same way as without Octane.

## Concurrent tasks and ticks

The package determines the current tenant for HTTP requests only. The closures passed to `Octane::concurrently()` and the callbacks of `Octane::tick()` run without a current tenant, also when Octane runs them in a separate worker, as Swoole does. Make the tenant current inside the closure, for instance with `$tenant->execute()`. The switch tenant tasks run there like they do in a request, and the package forgets the current tenant when the task or the tick terminates.
