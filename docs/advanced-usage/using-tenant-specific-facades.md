---
title: Using tenant specific facades
weight: 7
---

Facades behave like singletons. They only resolve once, and each use of the facade is against the same instance. For multitenancy, this may be problematic if the underlying instance behind a service, is built using tenant specific configuration.

If you only have a couple of tenant specific facade, we recommend only clearing them when switching a tenant. Here's a task that you could use for this.


```php
namespace App\Tenancy\SwitchTasks;

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Str;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tasks\SwitchTenantTask;

class ClearFacadeInstancesTask implements SwitchTenantTask
{
    public function makeCurrent(Tenant $tenant): void
    {
        tap($tenant);
    }

    public function forgetCurrent(): void
    {
        $facadeClasses = [
            // array containing class names of faces you wish to clear
        ];

        collect($facadeClasses)
            ->each(
                fn (string $facade) => $facade::clearResolvedInstance($facade::getFacadeAccessor);
            );
    }
}
```

Should you want to clear out all defined facades, you can use this code (provided by [Adrian Brown](https://github.com/spatie/laravel-multitenancy/discussions/240#discussion-3354768)) which will loop through all defined classes.

```php
namespace App\Tenancy\SwitchTasks;

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Str;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tasks\SwitchTenantTask;

class ClearFacadeInstancesTask implements SwitchTenantTask
{
    public function makeCurrent(Tenant $tenant): void
    {
        tap($tenant);
    }

    public function forgetCurrent(): void
    {
        $this->clearFacadeInstancesInTheAppNamespace();
    }

    protected function clearFacadeInstancesInTheAppNamespace(): void
    {
        // Discovers all facades in the App namespace and clears their resolved instance:
        collect(get_declared_classes())
            ->filter(fn ($className) => is_subclass_of($className, Facade::class))
            ->filter(fn ($className) => Str::startsWith($className, 'App') || Str::startsWith($className, 'Facades\\App'))
            ->each(fn ($className) => $className::clearResolvedInstance(
                $className::getFacadeAccessor()
            ));
    }
}
```
