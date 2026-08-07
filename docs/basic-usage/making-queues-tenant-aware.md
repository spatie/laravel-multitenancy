---
title: Making queues tenant aware
weight: 6
---

The package can make queues tenant aware. To enable this behaviour, set the `queues_are_tenant_aware_by_default` key in the `multitenancy` config file to `true`.

When the behaviour is enabled, the package will keep track of which tenant is the current one when a job is dispatched. That tenant will automatically be made the current one inside that job.

## Make specific jobs tenant aware

If you don't want to make all jobs tenant aware, you must set the `queues_are_tenant_aware_by_default` config key to `false`. Jobs that should be tenant aware should implement the empty marker interface `Spatie\Multitenancy\Jobs\TenantAware` or should be added to the config `tenant_aware_jobs`.

```php
use Illuminate\Contracts\Queue\ShouldQueue;
use Spatie\Multitenancy\Jobs\TenantAware;

class TestJob implements ShouldQueue, TenantAware
{
    public function handle()
    {
        // do the work
    }
}
```

or, using the config `multitenancy.php`:

```php
'tenant_aware_jobs' => [
    TestJob::class,
],
```

## Making specific jobs not tenant aware

Jobs that never should be tenant aware should implement the empty marker interface `Spatie\Multitenancy\Jobs\NotTenantAware` or should be added to the config `not_tenant_aware_jobs`.

```php
use Illuminate\Contracts\Queue\ShouldQueue;
use Spatie\Multitenancy\Jobs\NotTenantAware;

class TestJob implements ShouldQueue, NotTenantAware
{
    public function handle()
    {
        // do the work
    }
}
```

or, using the config `multitenancy.php`:

```php
'not_tenant_aware_jobs' => [
    TestJob::class,
],
```

## Queueing Closures

Dispatch a closure is slightly different from a job class because here, you can't implement `TenantAware` or `NotTenantAware` interfaces. The package can handle the queue closures by enabling the `queues_are_tenant_aware_by_default`, but if you enjoy keeping to `false` parameter, you can dispatch a tenant-aware job closure like so:

```php
$tenant = Tenant::current();

dispatch(function () use ($tenant) {
    $tenant->execute(function () {
        // Your job
    });
});
```

## When the tenant cannot be retrieved

If a tenant aware job is unable to retrieve the tenant, because the tenant was deleted before the job was processed, for example, the job will fail with an instance of `Spatie\Multitenancy\Exceptions\CurrentTenantCouldNotBeDeterminedInTenantAwareJob`.

On the other hand, a job that is not tenant aware runs without a current tenant. As such, it is important that your jobs make no assumptions about the active tenant unless they are tenant aware.

## The current tenant of the surrounding code

Processing a job never leaves the surrounding code with a different current tenant than it had before. Whatever tenant was current before the job started is restored when the job finishes, both when it succeeds and when it fails.

This matters when a job runs in the same process as the code that dispatched it, which is the case on the `sync` connection and when using `dispatchSync()`.

```php
$tenant->makeCurrent();

SomeNotTenantAwareJob::dispatchSync(); // runs without a current tenant

Tenant::current(); // still $tenant
```

Retrying a failed job works the same way. `queue:retry` needs the tenant of the failed job to be current while it pushes that job back onto the queue, but once the command has finished the tenant of the code that ran it is restored. This matters if you retry jobs from a request with `Artisan::call('queue:retry')`.
