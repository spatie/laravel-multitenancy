---
title: Automatically determining the current tenant
weight: 1
---

At the start of each request, the package will try to determine which tenant should be active for the current request. The package ships with a class named `DomainTenantFinder` that will try to find a `Tenant` whose `domain` attribute matches with the hostname of the current request.

In the `multitenancy` config file, you specify the tenant finder in the `tenant_finder` key.

```php
// in multitenancy.php
/*
 * This class is responsible for determining which tenant should be current
 * for the given request.
 *
 * This class should extend `Spatie\Multitenancy\TenantFinder\TenantFinder`
 *
 */
'tenant_finder' => Spatie\Multitenancy\TenantFinder\DomainTenantFinder::class,
```

If there is a tenant returned by the tenant finder, [all configured tasks](https://docs.spatie.be/laravel-multitenancy/v1/using-tasks-to-prepare-the-environment/overview/) will be performed on it. After that, the tenant instance will be bound in the container using the `currentTenant` key.

```php
app('currentTenant') // will return the current tenant or `null`
```

You can create a tenant finder of your own. A valid tenant finder is any class that extends `Spatie\Multitenancy\TenantFinder\TenantFinder`. You must implement this abstract method:

```php
abstract public function findForRequest(Request $request): ?Tenant;
```

Here's how the default `DomainTenantFinder` is implemented. The `getTenantModel` returns an instance of the class specified in the `tenant_model` key of the `multitenancy` config file.

```php
namespace Spatie\Multitenancy\TenantFinder;

use Illuminate\Http\Request;
use Spatie\Multitenancy\Models\Concerns\UsesTenantModel;
use Spatie\Multitenancy\Models\Tenant;

class DomainTenantFinder extends TenantFinder
{
    use UsesTenantModel;

    public function findForRequest(Request $request):?Tenant
    {
        $host = $request->getHost();

        return $this->getTenantModel()::whereDomain($host)->first();
    }
}
```
