---
title: Upgrade guide
weight: 2
---

In the `4.x` version, we have introduced the contract concept to the Tenant so that any model could implement the interface.

The first step to reach our goal is upgrading our package version.

```bash
composer require spatie/laravel-multitenancy:^4.0
```

### Removed `UsesTenantModel` trait
Remove any reference to our old trait `Spatie\Models\Concerns\UsesTenantModel`, because now the right `Tenant` instance can be resolved using `app(IsTenant::class)`.

### Tenant finder
If you are using the default finder included in the package, no changes are required by your side. However, when you are using a custom finder, you need to change the returned value in `findForRequest` method to `?IsTenant`. Example:

```php
use Illuminate\Http\Request;
use Spatie\Multitenancy\Contracts\IsTenant;

class YourCustomTenantFinder extends TenantFinder
{
    public function findForRequest(Request $request): ?IsTenant
    {
        // ...
    }
}
```

### Custom tasks
As has already been pointed out for the finder, the same change is required for any task because our `SwitchTenantTask` interface now is:
```php
public function makeCurrent(IsTenant $tenant): void;
```

So, it requires replacing the method parameter from `Tenant $tenant` to `IsTenant $tenant.`
