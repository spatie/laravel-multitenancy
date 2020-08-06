---
title: Executing commands tenant-aware
weight: 10
---

Commands can be made tenant aware by applying the `IsTenantAware` trait. When using the trait is is required to append `{--tenant=*}` to the command signature.
 
```php
use Illuminate\Console\Command;
use Spatie\Multitenancy\Commands\Concerns\IsTenantAware;

class YourFavouriteCommand extends Command
{
    use IsTenantAware;

    protected $signature = 'your-favorite-command {--tenant=*}';

    public function handle()
    {
        return $this->line('The tenant is '. Tenant::current()->name);
    }
}
```

When executing the command, the `handle` method will be called for each tenant. 

```bash
php artisan your-favorite-command 
```

Using the example above, the name of each tenant will be written to the output of the command.


You can also execute the command for a specific tenant:


```bash
php artisan your-favorite-command  --tenant=1
```
