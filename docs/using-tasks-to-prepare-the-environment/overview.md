---
title: Overview
weight: 1
---

When making a tenant the current one, the tasks inside the `switch_tenant_tasks` key of the `multitenancy` config file will be executed. Inside these tasks you can perform logic to configure the environment for the tenant that is being made the current one.

The philosophy of this package is that it should only provide the bare essentials to enable multitenancy. That's why it only provides two tasks out of the box. These tasks serve as example implementations.  

You can easily [create your own tasks](/laravel-multitenancy/v1/using-tasks-to-prepare-the-environment/creating-your-own-task/) that fit your particular project.

The package ships with these tasks:

- [switch the tenant database](/laravel-multitenancy/v1/using-tasks-to-prepare-the-environment/switching-databases) (required when using multiple tenant databases)
- [prefixing the cache](/laravel-multitenancy/v1/using-tasks-to-prepare-the-environment/prefixing-cache)

These tasks are optional. When you need one, just add it to the `switch_tenant_tasks` key of the `multitenancy` config file.
