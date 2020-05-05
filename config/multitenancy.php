<?php

return [
    /*
     * This class is responsible for determining which tenant should be current
     * for the given request.
     *
     * This class should extend `Spatie\Multitenancy\TenantFinder\TenantFinder`
     *
     */
    'tenant_finder' => '',

    /*
     * This class is the model used for storing configuration on tenants.
     *
     * It must be or extend `Spatie\Multitenancy\Models\Tenant::class`
     */
    'tenant_model' => \Spatie\Multitenancy\Models\Tenant::class,

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
