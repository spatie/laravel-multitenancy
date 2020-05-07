---
title: Overview
weight: 1
---

When making a tenant the current one, the tasks inside the `switch_tenant_tasks` key of the `multitenancy` config file will be executed. Inside these tasks you can perform logic to configure the environment for the tenant that is being made the current one.

The package ships with various tasks to:

- [switch the tenant database](/laravel-multitenancy/v1/using-tasks-to-prepare-the-environment/database) (required when using multiple tenant databases)
- [prefixing the cache](/laravel-multitenancy/v1/using-tasks-to-prepare-the-environment/cache)

All of these tasks are optional. When you need one, just add it to the `switch_tenant_tasks` key of the `multitenancy` config file.

You can also [create your own task](/laravel-multitenancy/v1/using-tasks-to-prepare-the-environment/creating-your-own-task/).
