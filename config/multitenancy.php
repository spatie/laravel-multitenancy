<?php

use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tasks\ConfigureCache;
use Spatie\Multitenancy\Tasks\ConfigureDatabase;
use Spatie\Multitenancy\TenantFinder\DomainTenantFinder;

return [
    /*
     * This class is responsible for determining which tenant should be current
     * for the given request.
     *
     * This class should extend `Spatie\Multitenancy\TenantFinder\TenantFinder`
     *
     */
    'tenant_finder' => DomainTenantFinder::class,

    /*
     * These tasks will be performed to make a tenant current.
     *
     * A valid task is any class that implements Spatie\Multitenancy\Tasks\MakeTenantCurrentTask
     */
    'make_tenant_current_tasks' => [
        ConfigureDatabase::class,
        ConfigureCache::class,
    ],

    /*
     * This class is the model used for storing configuration on tenants.
     *
     * It must be or extend `Spatie\Multitenancy\Models\Tenant::class`
     */
    'tenant_model' => Tenant::class,

    /*
     * If there is a current tenant when dispatching a job, the id of the current tenant
     * will be automatically set on the job. When the job is executed, the set
     * tenant on the job will be made current.
     */
    'tenant_aware_queue' => true,

    /*
     * The connection name to reach the a tenant database
     */
    'tenant_connection_name' => 'tenant',

    /*
     * The connection name to reach the a landlord database
     */
    'landlord_connection_name' => 'landlord',
];
