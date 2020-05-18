---
title: Executing artisan commands for each tenant
weight: 3
---

If you want to execute an artisan command for all tenants, you can use `tenants:artisan <artisan command>`. This command will loop over tenants and for each of them make that tenant current, and execute the artisan command.

When your tenants each have their own database, you could migrate each tenant database with this command (given you are using a task like [`SwitchTenantDatabase`](https://docs.spatie.be/laravel-multitenancy/v1/using-tasks-to-prepare-the-environment/switching-databases)):

```bash
php artisan tenants:artisan migrate
```

We are using the `migrate` command here, but you can pass any command that you like.

## Passing arguments and options

If you use quotes around the command part you can use any argument and option that the command supports.

```bash
php artisan tenants:artisan "migrate --seed"
```

## Running artisan command for specific tenants

If the command only needs to run for specific tenant, you can pass its `id` to the `tenant` option.

```bash
php artisan tenants:artisan "migrate --seed" --tenant=123
```
