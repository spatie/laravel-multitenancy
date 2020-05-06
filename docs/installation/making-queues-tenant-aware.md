---
title: Making queues tenant aware
weight: 4
---

The package can optionally make queues tenant aware by automatically injecting `tenant_id` to each job that is queued when there is a "current" tenant. When processing the job that tenant will automatically be made the current one.

You can activate this behaviour by setting the `tenant_aware_queue` key in the `multitenancy` config file to `true`.
