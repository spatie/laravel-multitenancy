---
title: Executing commands tenant-aware
weight: 10
---

When the `tenants:artisan` is not enough, creating commands tenant-aware could be useful: it's the case of a command that should run with the `schedule:run`.
 
Do it is quite simple using the built-in trait `Spatie\Multitenancy\Commands\Concerns\IsTenantAware` in your command.

```php
class TenantNoopCommand extends \Illuminate\Console\Command
{
    use \Spatie\Multitenancy\Commands\Concerns\IsTenantAware;

    protected $signature = 'tenant:noop {--tenant=*}';

    public function handle()
    {
        return $this->line('The tenant is '. Tenant::current()->id);
    }
}
```

Please remember that it's crucial to extend your command signature with `{--tenant=*}`.

Finally, you can execute the command for only one tenant
```bash
php artisan tenant:noop --tenant=1
```

Or for all tenants
```bash
php artisan tenant:noop
```
